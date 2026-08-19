<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->guard();
        $fin = new FinanceService();
        $pdo = db();

        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        // ===== Kartu =====
        $saldoKas = $fin->saldoKas();
        $pemasukanBulan = $fin->totalMasuk($monthStart, $monthEnd);
        $pengeluaranBulan = $fin->totalKeluar($monthStart, $monthEnd);
        $penjualanBulan = (float)$pdo->query(
            'SELECT COALESCE(SUM(total),0) FROM transactions WHERE type="penjualan" AND status="AKTIF" AND tanggal BETWEEN "' . $monthStart . '" AND "' . $monthEnd . '"'
        )->fetchColumn();
        $pembelianBulan = (float)$pdo->query(
            'SELECT COALESCE(SUM(total),0) FROM transactions WHERE type="pembelian" AND status="AKTIF" AND tanggal BETWEEN "' . $monthStart . '" AND "' . $monthEnd . '"'
        )->fetchColumn();

        $piutangTotal = (float)$pdo->query(
            'SELECT COALESCE(SUM(r.total - COALESCE((SELECT SUM(p.nominal) FROM receivable_payments p WHERE p.receivable_id = r.id AND p.status="AKTIF"),0)),0)
             FROM receivables r WHERE r.status="AKTIF"'
        )->fetchColumn();
        $hutangTotal = (float)$pdo->query(
            'SELECT COALESCE(SUM(pb.total - COALESCE((SELECT SUM(p.nominal) FROM payable_payments p WHERE p.payable_id = pb.id AND p.status="AKTIF"),0)),0)
             FROM payables pb WHERE pb.status="AKTIF"'
        )->fetchColumn();

        // Estimasi laba/rugi bulan ini: Pendapatan - HPP - Biaya Operasional + Pemasukan Lain
        // CATATAN: biaya operasional = transaksi pengeluaran (BUKAN seluruh kas keluar,
        // karena pembelian tunai hanyalah kas -> stok dan sudah tercakup di HPP).
        $hpp = (float)$pdo->query(
            'SELECT COALESCE(SUM(td.qty * pr.harga_beli),0)
             FROM transaction_details td
             JOIN transactions t ON t.id = td.transaction_id
             JOIN products pr ON pr.id = td.product_id
             WHERE t.type="penjualan" AND t.status="AKTIF" AND t.tanggal BETWEEN "' . $monthStart . '" AND "' . $monthEnd . '"'
        )->fetchColumn();
        $pengeluaranOperasional = (float)$pdo->query(
            'SELECT COALESCE(SUM(total),0) FROM transactions WHERE type="pengeluaran" AND status="AKTIF" AND tanggal BETWEEN "' . $monthStart . '" AND "' . $monthEnd . '"'
        )->fetchColumn();
        $pemasukanLainBulan = (float)$pdo->query(
            'SELECT COALESCE(SUM(total),0) FROM transactions WHERE type="pemasukan" AND status="AKTIF" AND tanggal BETWEEN "' . $monthStart . '" AND "' . $monthEnd . '"'
        )->fetchColumn();
        $estimasiLaba = $penjualanBulan - $hpp - $pengeluaranOperasional + $pemasukanLainBulan;

        // ===== Grafik 1: pemasukan vs pengeluaran 12 bulan =====
        $bulanLabels = [];
        $seriMasuk = [];
        $seriKeluar = [];
        for ($i = 11; $i >= 0; $i--) {
            $t = strtotime("first day of -$i months");
            $bulanLabels[] = date('M Y', $t);
            $bStart = date('Y-m-01', $t);
            $bEnd = date('Y-m-t', $t);
            $seriMasuk[] = $fin->totalMasuk($bStart, $bEnd);
            $seriKeluar[] = $fin->totalKeluar($bStart, $bEnd);
        }

        // ===== Grafik 2: penjualan 7 hari terakhir =====
        $hariLabels = [];
        $seriJual = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $hariLabels[] = tanggal($d);
            $seriJual[] = (float)$pdo->query(
                'SELECT COALESCE(SUM(total),0) FROM transactions WHERE type="penjualan" AND status="AKTIF" AND tanggal = "' . $d . '"'
            )->fetchColumn();
        }

        // ===== Grafik 3: kategori penjualan =====
        $katLabels = [];
        $katNilai = [];
        $rowsKat = $pdo->query(
            'SELECT COALESCE(c.name, "Tanpa Kategori") AS nama, SUM(td.qty * td.harga) AS total
             FROM transaction_details td
             JOIN transactions t ON t.id = td.transaction_id
             LEFT JOIN products pr ON pr.id = td.product_id
             LEFT JOIN categories c ON c.id = pr.category_id
             WHERE t.type="penjualan" AND t.status="AKTIF"
             GROUP BY c.name ORDER BY total DESC LIMIT 6'
        )->fetchAll();
        foreach ($rowsKat as $r) {
            $katLabels[] = $r['nama'];
            $katNilai[] = (float)$r['total'];
        }

        // ===== Transaksi terbaru =====
        $recent = $pdo->query(
            'SELECT * FROM (
                SELECT t.tanggal, t.no_transaksi AS no, "Penjualan" AS jenis, COALESCE(t.keterangan, t.no_transaksi) AS keterangan, t.total AS nominal, u.username AS username, t.type
                FROM transactions t JOIN users u ON u.id = t.user_id WHERE t.type="penjualan" AND t.status="AKTIF"
                UNION ALL
                SELECT t.tanggal, t.no_transaksi, "Pembelian", COALESCE(t.keterangan, t.no_transaksi), t.total, u.username, t.type
                FROM transactions t JOIN users u ON u.id = t.user_id WHERE t.type="pembelian" AND t.status="AKTIF"
                UNION ALL
                SELECT t.tanggal, t.no_transaksi, "Pemasukan Lain", COALESCE(t.keterangan, t.no_transaksi), t.total, u.username, t.type
                FROM transactions t JOIN users u ON u.id = t.user_id WHERE t.type="pemasukan" AND t.status="AKTIF"
                UNION ALL
                SELECT t.tanggal, t.no_transaksi, "Pengeluaran", COALESCE(t.keterangan, t.no_transaksi), t.total, u.username, t.type
                FROM transactions t JOIN users u ON u.id = t.user_id WHERE t.type="pengeluaran" AND t.status="AKTIF"
                UNION ALL
                SELECT p.tanggal, p.no_bukti, "Bayar Piutang", COALESCE(p.keterangan, p.no_bukti), p.nominal, u.username, "piutang"
                FROM receivable_payments p JOIN users u ON u.id = p.user_id WHERE p.status="AKTIF"
                UNION ALL
                SELECT p.tanggal, p.no_bukti, "Bayar Hutang", COALESCE(p.keterangan, p.no_bukti), p.nominal, u.username, "hutang"
                FROM payable_payments p JOIN users u ON u.id = p.user_id WHERE p.status="AKTIF"
            ) x ORDER BY tanggal DESC, no DESC LIMIT 10'
        )->fetchAll();

        // ===== Indikator =====
        $stokMenipis = $pdo->query(
            'SELECT kode, name, stock, stock_minimum FROM products WHERE is_active = 1 AND stock <= stock_minimum ORDER BY stock ASC LIMIT 5'
        )->fetchAll();
        $piutangJatuhTempo = $pdo->query(
            'SELECT r.id, c.name AS pelanggan, r.jatuh_tempo, r.total
             FROM receivables r JOIN customers c ON c.id = r.customer_id
             WHERE r.status="AKTIF" AND r.jatuh_tempo IS NOT NULL AND r.jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY r.jatuh_tempo LIMIT 5'
        )->fetchAll();
        $hutangJatuhTempo = $pdo->query(
            'SELECT p.id, s.name AS supplier, p.jatuh_tempo, p.total
             FROM payables p JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.status="AKTIF" AND p.jatuh_tempo IS NOT NULL AND p.jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY p.jatuh_tempo LIMIT 5'
        )->fetchAll();
        $saldoMinimum = (float)setting('saldo_minimum_cash', '500000');
        $saldoRendah = $saldoKas < $saldoMinimum;

        $this->render('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'saldoKas' => $saldoKas,
            'pemasukanBulan' => $pemasukanBulan,
            'pengeluaranBulan' => $pengeluaranBulan,
            'penjualanBulan' => $penjualanBulan,
            'pembelianBulan' => $pembelianBulan,
            'piutangTotal' => $piutangTotal,
            'hutangTotal' => $hutangTotal,
            'estimasiLaba' => $estimasiLaba,
            'bulanLabels' => json_encode($bulanLabels),
            'seriMasuk' => json_encode($seriMasuk),
            'seriKeluar' => json_encode($seriKeluar),
            'hariLabels' => json_encode($hariLabels),
            'seriJual' => json_encode($seriJual),
            'katLabels' => json_encode($katLabels),
            'katNilai' => json_encode($katNilai),
            'recent' => $recent,
            'stokMenipis' => $stokMenipis,
            'piutangJatuhTempo' => $piutangJatuhTempo,
            'hutangJatuhTempo' => $hutangJatuhTempo,
            'saldoRendah' => $saldoRendah,
            'saldoMinimum' => $saldoMinimum,
            'pageScripts' => ['chart.umd.min.js', 'dashboard.js'],
        ]);
    }
}
