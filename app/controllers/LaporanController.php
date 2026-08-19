<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class LaporanController extends Controller
{
    private const TABS = ['kas','penjualan','pembelian','pemasukan','pengeluaran','labarugi','piutang','hutang','stok','bulanan','tahunan'];

    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $tab = $this->tab();
        $data = $this->buildReport($tab);
        $data['pageTitle'] = 'Laporan';
        $data['tab'] = $tab;
        $this->render('laporan/index', $data);
    }

    public function print(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $tab = $this->tab();
        $data = $this->buildReport($tab);
        $data['tab'] = $tab;
        $data['profile'] = koperasi_profile();
        $this->renderPrint('laporan/print', $data);
    }

    public function export(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $tab = $this->tab();
        $data = $this->buildReport($tab);
        $this->renderExcel($tab, $data);
    }

    private function tab(): string
    {
        $tab = input('tab', 'kas');
        return in_array($tab, self::TABS, true) ? $tab : 'kas';
    }

    private function q(string $sql, array $params = []): array
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function oneValue(string $sql, array $params = [])
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function periode(): array
    {
        return [input('dari', date('Y-m-01')), input('sampai', date('Y-m-d'))];
    }

    private function buildReport(string $tab): array
    {
        $m = 'report' . ucfirst($tab);
        return method_exists($this, $m) ? $this->$m() : $this->reportKas();
    }

    private function base(string $title, array $columns, array $rows, array $totals, array $moneyCols = []): array
    {
        list($dari, $sampai) = $this->periode();
        return ['title' => $title, 'dari' => $dari, 'sampai' => $sampai, 'columns' => $columns, 'rows' => $rows, 'totals' => $totals, 'moneyCols' => $moneyCols];
    }

    private function reportKas(): array
    {
        list($dari, $sampai) = $this->periode();
        $fin = new FinanceService();
        $buku = $fin->bukuKas($dari, $sampai);

        $totMasuk = 0;
        $totKeluar = 0;
        $out = [];
        foreach ($buku['rows'] as $r) {
            if ($r['jenis'] === 'masuk' || $r['jenis'] === 'saldo_awal') {
                $totMasuk += (float)$r['nominal'];
            }
            if ($r['jenis'] === 'keluar') {
                $totKeluar += (float)$r['nominal'];
            }
            $out[] = [
                'tanggal' => tanggal($r['tanggal']), 'no_transaksi' => $r['no_transaksi'], 'jenis' => $r['jenis'],
                'keterangan' => $r['keterangan'],
                'masuk' => ($r['jenis'] === 'masuk' || $r['jenis'] === 'saldo_awal') ? (float)$r['nominal'] : 0,
                'keluar' => $r['jenis'] === 'keluar' ? (float)$r['nominal'] : 0,
                'saldo' => (float)$r['saldo'],
            ];
        }
        $data = $this->base('Laporan Kas', [
            'tanggal' => 'Tanggal', 'no_transaksi' => 'No Transaksi', 'jenis' => 'Jenis', 'keterangan' => 'Keterangan',
            'masuk' => 'Kas Masuk', 'keluar' => 'Kas Keluar', 'saldo' => 'Saldo',
        ], $out, ['kas_awal' => $buku['saldo_awal'], 'kas_masuk' => $totMasuk, 'kas_keluar' => $totKeluar, 'kas_akhir' => $buku['saldo_akhir']], ['masuk','keluar','saldo']);
        $data['jenisLabel'] = ['saldo_awal' => 'Saldo Awal', 'masuk' => 'Kas Masuk', 'keluar' => 'Kas Keluar'];
        return $data;
    }

    private function reportPenjualan(): array
    {
        list($dari, $sampai) = $this->periode();
        $rows = $this->q('SELECT t.*, c.name AS pelanggan FROM transactions t LEFT JOIN customers c ON c.id = t.customer_id WHERE t.type="penjualan" AND t.status="AKTIF" AND t.tanggal BETWEEN ? AND ? ORDER BY t.tanggal, t.id', [$dari, $sampai]);
        $total = array_sum(array_map(fn($r) => (float)$r['total'], $rows));
        $tunai = array_sum(array_map(fn($r) => $r['payment_method'] === 'tunai' ? (float)$r['total'] : 0, $rows));
        $kredit = $total - $tunai;
        $out = array_map(fn($r) => [
            'tanggal' => tanggal($r['tanggal']), 'no_transaksi' => $r['no_transaksi'], 'pelanggan' => $r['pelanggan'] ?? 'Umum',
            'metode' => ucfirst($r['payment_method']), 'total' => (float)$r['total'],
        ], $rows);
        return $this->base('Laporan Penjualan', ['tanggal' => 'Tanggal', 'no_transaksi' => 'No Transaksi', 'pelanggan' => 'Pelanggan', 'metode' => 'Metode', 'total' => 'Total'], $out, ['total' => $total, 'tunai' => $tunai, 'kredit' => $kredit], ['total']);
    }

    private function reportPembelian(): array
    {
        list($dari, $sampai) = $this->periode();
        $rows = $this->q('SELECT t.*, s.name AS supplier FROM transactions t LEFT JOIN suppliers s ON s.id = t.supplier_id WHERE t.type="pembelian" AND t.status="AKTIF" AND t.tanggal BETWEEN ? AND ? ORDER BY t.tanggal, t.id', [$dari, $sampai]);
        $total = array_sum(array_map(fn($r) => (float)$r['total'], $rows));
        $tunai = array_sum(array_map(fn($r) => $r['payment_method'] === 'tunai' ? (float)$r['total'] : 0, $rows));
        $kredit = $total - $tunai;
        $out = array_map(fn($r) => [
            'tanggal' => tanggal($r['tanggal']), 'no_transaksi' => $r['no_transaksi'], 'supplier' => $r['supplier'] ?? '-',
            'metode' => ucfirst($r['payment_method']), 'total' => (float)$r['total'],
        ], $rows);
        return $this->base('Laporan Pembelian', ['tanggal' => 'Tanggal', 'no_transaksi' => 'No Transaksi', 'supplier' => 'Supplier', 'metode' => 'Metode', 'total' => 'Total'], $out, ['total' => $total, 'tunai' => $tunai, 'kredit' => $kredit], ['total']);
    }

    private function reportPemasukan(): array
    {
        list($dari, $sampai) = $this->periode();
        $rows = $this->q('SELECT t.*, c.name AS kategori FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE t.type="pemasukan" AND t.status="AKTIF" AND t.tanggal BETWEEN ? AND ? ORDER BY t.tanggal, t.id', [$dari, $sampai]);
        $total = array_sum(array_map(fn($r) => (float)$r['total'], $rows));
        $per = [];
        foreach ($rows as $r) { $k = $r['kategori'] ?? 'Lainnya'; $per[$k] = ($per[$k] ?? 0) + (float)$r['total']; }
        $out = array_map(fn($r) => ['tanggal' => tanggal($r['tanggal']), 'no_transaksi' => $r['no_transaksi'], 'kategori' => $r['kategori'] ?? '-', 'total' => (float)$r['total']], $rows);
        return $this->base('Laporan Pemasukan', ['tanggal' => 'Tanggal', 'no_transaksi' => 'No Transaksi', 'kategori' => 'Kategori', 'total' => 'Nominal'], $out, ['total' => $total, 'per_kategori' => $per], ['total']);
    }

    private function reportPengeluaran(): array
    {
        list($dari, $sampai) = $this->periode();
        $rows = $this->q('SELECT t.*, c.name AS kategori FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE t.type="pengeluaran" AND t.status="AKTIF" AND t.tanggal BETWEEN ? AND ? ORDER BY t.tanggal, t.id', [$dari, $sampai]);
        $total = array_sum(array_map(fn($r) => (float)$r['total'], $rows));
        $per = [];
        foreach ($rows as $r) { $k = $r['kategori'] ?? 'Lainnya'; $per[$k] = ($per[$k] ?? 0) + (float)$r['total']; }
        $out = array_map(fn($r) => ['tanggal' => tanggal($r['tanggal']), 'no_transaksi' => $r['no_transaksi'], 'kategori' => $r['kategori'] ?? '-', 'total' => (float)$r['total']], $rows);
        return $this->base('Laporan Pengeluaran', ['tanggal' => 'Tanggal', 'no_transaksi' => 'No Transaksi', 'kategori' => 'Kategori', 'total' => 'Nominal'], $out, ['total' => $total, 'per_kategori' => $per], ['total']);
    }

    private function reportLabarugi(): array
    {
        list($dari, $sampai) = $this->periode();
        $pendapatan = (float)$this->oneValue('SELECT COALESCE(SUM(total),0) FROM transactions WHERE type="penjualan" AND status="AKTIF" AND tanggal BETWEEN ? AND ?', [$dari, $sampai]);
        $pemasukanLain = (float)$this->oneValue('SELECT COALESCE(SUM(total),0) FROM transactions WHERE type="pemasukan" AND status="AKTIF" AND tanggal BETWEEN ? AND ?', [$dari, $sampai]);
        $hpp = (float)$this->oneValue('SELECT COALESCE(SUM(td.qty * pr.harga_beli),0) FROM transaction_details td JOIN transactions t ON t.id = td.transaction_id JOIN products pr ON pr.id = td.product_id WHERE t.type="penjualan" AND t.status="AKTIF" AND t.tanggal BETWEEN ? AND ?', [$dari, $sampai]);
        $biaya = (float)$this->oneValue('SELECT COALESCE(SUM(total),0) FROM transactions WHERE type="pengeluaran" AND status="AKTIF" AND tanggal BETWEEN ? AND ?', [$dari, $sampai]);
        $laba = $pendapatan - $hpp - $biaya + $pemasukanLain;

        $rows = [
            ['uraian' => 'A. Pendapatan Usaha (Penjualan)', 'nilai' => $pendapatan, 'tag' => 'pendapatan'],
            ['uraian' => 'B. Harga Pokok Penjualan (HPP)', 'nilai' => -$hpp, 'tag' => 'biaya'],
            ['uraian' => 'C. Laba Kotor', 'nilai' => $pendapatan - $hpp, 'tag' => 'kotor'],
            ['uraian' => 'D. Biaya Operasional', 'nilai' => -$biaya, 'tag' => 'biaya'],
            ['uraian' => 'E. Pemasukan Lain (non-penjualan)', 'nilai' => $pemasukanLain, 'tag' => 'pendapatan'],
            ['uraian' => 'F. Laba/Rugi Bersih', 'nilai' => $laba, 'tag' => 'bersih'],
        ];
        $data = $this->base('Laporan Laba/Rugi Sederhana', ['uraian' => 'Uraian', 'nilai' => 'Nilai'], $rows, ['laba_rugi' => $laba], ['nilai']);
        $data['subtitle'] = 'Non-akuntansi (tanpa jurnal penuh)';
        return $data;
    }

    private function reportPiutang(): array
    {
        $rows = $this->q('SELECT r.*, c.name AS pelanggan, r.total - COALESCE((SELECT SUM(p.nominal) FROM receivable_payments p WHERE p.receivable_id = r.id AND p.status="AKTIF"),0) AS sisa FROM receivables r JOIN customers c ON c.id = r.customer_id WHERE r.status="AKTIF" ORDER BY r.tanggal DESC');
        $out = [];
        $tot = 0;
        foreach ($rows as $r) {
            $sisa = (float)$r['sisa'];
            $dibayar = (float)$r['total'] - $sisa;
            $tot += $sisa;
            list($label) = status_tagihan($dibayar, $sisa);
            $out[] = ['tanggal' => tanggal($r['tanggal']), 'no_transaksi' => $r['no_transaksi'], 'pelanggan' => $r['pelanggan'], 'total' => (float)$r['total'], 'dibayar' => $dibayar, 'sisa' => $sisa, 'jatuh_tempo' => tanggal($r['jatuh_tempo']), 'status' => $label];
        }
        $data = $this->base('Laporan Piutang', ['tanggal' => 'Tanggal', 'no_transaksi' => 'No Transaksi', 'pelanggan' => 'Pelanggan', 'total' => 'Total', 'dibayar' => 'Dibayar', 'sisa' => 'Sisa', 'jatuh_tempo' => 'Jatuh Tempo', 'status' => 'Status'], $out, ['sisa' => $tot], ['total','dibayar','sisa']);
        $data['no_periode'] = true;
        return $data;
    }

    private function reportHutang(): array
    {
        $rows = $this->q('SELECT p.*, s.name AS supplier, p.total - COALESCE((SELECT SUM(pm.nominal) FROM payable_payments pm WHERE pm.payable_id = p.id AND pm.status="AKTIF"),0) AS sisa FROM payables p JOIN suppliers s ON s.id = p.supplier_id WHERE p.status="AKTIF" ORDER BY p.tanggal DESC');
        $out = [];
        $tot = 0;
        foreach ($rows as $r) {
            $sisa = (float)$r['sisa'];
            $dibayar = (float)$r['total'] - $sisa;
            $tot += $sisa;
            list($label) = status_tagihan($dibayar, $sisa);
            $out[] = ['tanggal' => tanggal($r['tanggal']), 'no_transaksi' => $r['no_transaksi'], 'supplier' => $r['supplier'], 'total' => (float)$r['total'], 'dibayar' => $dibayar, 'sisa' => $sisa, 'jatuh_tempo' => tanggal($r['jatuh_tempo']), 'status' => $label];
        }
        $data = $this->base('Laporan Hutang', ['tanggal' => 'Tanggal', 'no_transaksi' => 'No Transaksi', 'supplier' => 'Supplier', 'total' => 'Total', 'dibayar' => 'Dibayar', 'sisa' => 'Sisa', 'jatuh_tempo' => 'Jatuh Tempo', 'status' => 'Status'], $out, ['sisa' => $tot], ['total','dibayar','sisa']);
        $data['no_periode'] = true;
        return $data;
    }

    private function reportStok(): array
    {
        $rows = $this->q('SELECT p.kode, p.name, c.name AS kategori, p.satuan, p.stock, p.stock_minimum, p.harga_beli, (p.stock * p.harga_beli) AS nilai FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 ORDER BY p.name');
        $totNilai = array_sum(array_map(fn($r) => (float)$r['nilai'], $rows));
        $out = array_map(fn($r) => ['kode' => $r['kode'], 'name' => $r['name'], 'kategori' => $r['kategori'] ?? '-', 'satuan' => $r['satuan'], 'stock' => (float)$r['stock'], 'stock_minimum' => (float)$r['stock_minimum'], 'harga_beli' => (float)$r['harga_beli'], 'nilai' => (float)$r['nilai']], $rows);
        $data = $this->base('Laporan Stok', ['kode' => 'Kode', 'name' => 'Barang', 'kategori' => 'Kategori', 'satuan' => 'Satuan', 'stock' => 'Stok', 'stock_minimum' => 'Min.', 'harga_beli' => 'Harga Beli', 'nilai' => 'Nilai Stok'], $out, ['nilai' => $totNilai], ['harga_beli','nilai']);
        $data['no_periode'] = true;
        return $data;
    }

    private function reportBulanan(): array
    {
        $tahun = (int)input('tahun', date('Y'));
        $rows = $this->q(
            'SELECT DATE_FORMAT(tanggal, "%Y-%m") AS bulan, jenis, SUM(nominal) AS total
             FROM cash_transactions WHERE status="AKTIF" AND YEAR(tanggal) = ? GROUP BY DATE_FORMAT(tanggal, "%Y-%m"), jenis ORDER BY bulan',
            [$tahun]
        );
        $perBulan = [];
        foreach ($rows as $r) {
            $perBulan[$r['bulan']][$r['jenis']] = (float)$r['total'];
        }
        $out = [];
        $saldoKumulatif = 0;
        $namaBulan = [1 => 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf('%04d-%02d', $tahun, $m);
            $masuk = $perBulan[$key]['masuk'] ?? 0;
            $keluar = $perBulan[$key]['keluar'] ?? 0;
            $saldoKumulatif += $masuk - $keluar;
            $out[] = ['bulan' => $namaBulan[$m], 'masuk' => $masuk, 'keluar' => $keluar, 'selisih' => $masuk - $keluar, 'saldo_kumulatif' => $saldoKumulatif];
        }
        $data = $this->base('Rekap Keuangan Bulanan', ['bulan' => 'Bulan', 'masuk' => 'Kas Masuk', 'keluar' => 'Kas Keluar', 'selisih' => 'Selisih', 'saldo_kumulatif' => 'Saldo Kumulatif'], $out, ['tahun' => $tahun], ['masuk','keluar','selisih','saldo_kumulatif']);
        $data['no_periode'] = true;
        $data['tahun'] = $tahun;
        return $data;
    }

    private function reportTahunan(): array
    {
        $rows = $this->q(
            'SELECT YEAR(tanggal) AS tahun, jenis, SUM(nominal) AS total
             FROM cash_transactions WHERE status="AKTIF" GROUP BY YEAR(tanggal), jenis ORDER BY tahun'
        );
        $perTahun = [];
        foreach ($rows as $r) {
            $perTahun[$r['tahun']][$r['jenis']] = (float)$r['total'];
        }
        $out = [];
        $saldoKumulatif = 0;
        foreach ($perTahun as $tahun => $data) {
            $masuk = $data['masuk'] ?? 0;
            $keluar = $data['keluar'] ?? 0;
            $saldoKumulatif += $masuk - $keluar;
            $out[] = ['tahun' => $tahun, 'masuk' => $masuk, 'keluar' => $keluar, 'selisih' => $masuk - $keluar, 'saldo_kumulatif' => $saldoKumulatif];
        }
        $data = $this->base('Rekap Keuangan Tahunan', ['tahun' => 'Tahun', 'masuk' => 'Kas Masuk', 'keluar' => 'Kas Keluar', 'selisih' => 'Selisih', 'saldo_kumulatif' => 'Saldo Kumulatif'], $out, ['laporan' => 'Seluruh Tahun'], ['masuk','keluar','selisih','saldo_kumulatif']);
        $data['no_periode'] = true;
        return $data;
    }

    private function renderExcel(string $tab, array $data): void
    {
        $periode = '';
        if (empty($data['no_periode'])) {
            $periode = 'Periode: ' . tanggal($data['dari']) . ' s.d. ' . tanggal($data['sampai']);
        } elseif ($tab === 'bulanan' && isset($data['tahun'])) {
            $periode = 'Tahun: ' . $data['tahun'];
        }
        download_excel($data['title'], $data['columns'], $data['rows'], $data['moneyCols'] ?? [], $periode);
    }
}