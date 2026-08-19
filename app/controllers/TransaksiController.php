<?php

require_once APP_ROOT . '/app/core/Controller.php';

class TransaksiController extends Controller
{
    private const UNION = 'SELECT t.tanggal, t.no_transaksi, "Penjualan" AS jenis, c.name AS rekanan,
                                 t.payment_method, t.total, t.keterangan, u.username
                          FROM transactions t
                          LEFT JOIN customers c ON c.id = t.customer_id
                          JOIN users u ON u.id = t.user_id
                          WHERE t.type = "penjualan" AND t.status = "AKTIF" AND t.tanggal BETWEEN ? AND ?
                          UNION ALL
                          SELECT t.tanggal, t.no_transaksi, "Pembelian" AS jenis, s.name AS rekanan,
                                 t.payment_method, t.total, t.keterangan, u.username
                          FROM transactions t
                          LEFT JOIN suppliers s ON s.id = t.supplier_id
                          JOIN users u ON u.id = t.user_id
                          WHERE t.type = "pembelian" AND t.status = "AKTIF" AND t.tanggal BETWEEN ? AND ?';

    /**
     * Riwayat Transaksi: gabungan penjualan & pembelian, read-only.
     * Hanya transaksi AKTIF yang tampil (yang dibatalkan tidak ditampilkan).
     */
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);

        list($dari, $sampai, $q, $jenis) = $this->filters();
        list($whereSql, $params) = $this->whereParams($dari, $sampai, $q, $jenis);

        $countSql = 'SELECT COUNT(*) FROM (' . self::UNION . ') x WHERE ' . $whereSql;
        $dataSql = 'SELECT * FROM (' . self::UNION . ') x WHERE ' . $whereSql;
        $pg = paginate_data($countSql, $dataSql, $params, 'ORDER BY x.tanggal DESC, x.no_transaksi DESC', 20);

        $this->render('transaksi/index', [
            'pageTitle' => 'Riwayat Transaksi',
            'pg' => $pg,
            'dari' => $dari,
            'sampai' => $sampai,
            'q' => $q,
            'jenis' => $jenis,
        ]);
    }

    public function print(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $data = $this->buildReport();
        $data['profile'] = koperasi_profile();
        $this->renderPrint('transaksi/print', $data);
    }

    public function export(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $data = $this->buildReport();
        $periode = 'Periode: ' . tanggal($data['dari']) . ' s.d. ' . tanggal($data['sampai']);
        download_excel($data['title'], $data['columns'], $data['rows'], $data['moneyCols'], $periode);
    }

    private function filters(): array
    {
        return [
            input('dari', date('Y-m-01')),
            input('sampai', date('Y-m-d')),
            trim(input('q', '')),
            input('jenis', ''),
        ];
    }

    private function whereParams(string $dari, string $sampai, string $q, string $jenis): array
    {
        $where = ['1=1'];
        $params = [$dari, $sampai, $dari, $sampai];
        if ($jenis === 'penjualan' || $jenis === 'pembelian') {
            $where[] = 'x.jenis = ?';
            $params[] = $jenis === 'penjualan' ? 'Penjualan' : 'Pembelian';
        }
        if ($q !== '') {
            $where[] = '(x.no_transaksi LIKE ? OR x.rekanan LIKE ? OR x.keterangan LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        return [implode(' AND ', $where), $params];
    }

    /** Data penuh (tanpa pagination) untuk cetak/ekspor. */
    private function buildReport(): array
    {
        list($dari, $sampai, $q, $jenis) = $this->filters();
        list($whereSql, $params) = $this->whereParams($dari, $sampai, $q, $jenis);

        $stmt = db()->prepare('SELECT * FROM (' . self::UNION . ') x WHERE ' . $whereSql . ' ORDER BY x.tanggal DESC, x.no_transaksi DESC');
        $stmt->execute($params);
        $rows = [];
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [
                'tanggal' => tanggal($r['tanggal']),
                'no_transaksi' => $r['no_transaksi'],
                'jenis' => $r['jenis'],
                'rekanan' => $r['rekanan'] ?? '-',
                'payment_method' => ucfirst($r['payment_method']),
                'keterangan' => $r['keterangan'],
                'total' => (float)$r['total'],
                'username' => $r['username'],
            ];
        }

        return [
            'title' => 'Riwayat Transaksi',
            'dari' => $dari,
            'sampai' => $sampai,
            'columns' => [
                'tanggal' => 'Tanggal', 'no_transaksi' => 'No Transaksi', 'jenis' => 'Jenis',
                'rekanan' => 'Pelanggan / Supplier', 'payment_method' => 'Metode',
                'keterangan' => 'Keterangan', 'total' => 'Total', 'username' => 'User',
            ],
            'rows' => $rows,
            'moneyCols' => ['total'],
        ];
    }
}
