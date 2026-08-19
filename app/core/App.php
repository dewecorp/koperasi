<?php

/**
 * Router ringan.
 * Mendukung URL bersih (tanpa index.php / ekstensi .php):
 *   /dashboard, /barang, /barang/create, /barang/show/3
 *   /transaksi/penjualan, /transaksi/pembelian, /transaksi/pemasukan, /transaksi/pengeluaran
 *   /keuangan/kas, /keuangan/kas/saldo, /keuangan/piutang, /keuangan/hutang, /keuangan/modal
 *   /laporan/kas, /laporan/penjualan, ...
 *   /master/barang, /master/kategori, /master/supplier, /master/pelanggan
 *   /login, /logout, /password, /pengaturan, /backup, /log, /profil, /pengguna
 *
 * Juga tetap menerima fallback query lama: index.php?page=...&action=...
 */
class App
{
    /** Mapur rute bertingkat -> halaman + parameter. */
    private const GROUP_TRANSAKSI = [
        'penjualan' => 'penjualan', 'pembelian' => 'pembelian',
        'pemasukan' => 'pemasukan', 'pengeluaran' => 'pengeluaran',
    ];
    private const GROUP_KEUANGAN = [
        'kas' => 'kas', 'piutang' => 'piutang', 'hutang' => 'hutang', 'modal' => 'modal',
    ];
    private const GROUP_MASTER = [
        'barang' => 'barang', 'kategori' => 'kategori', 'supplier' => 'supplier', 'pelanggan' => 'pelanggan',
    ];
    private const LAPORAN_TAB = [
        'kas' => 'kas', 'penjualan' => 'penjualan', 'pembelian' => 'pembelian',
        'pemasukan' => 'pemasukan', 'pengeluaran' => 'pengeluaran',
        'labarugi' => 'labarugi', 'laba-rugi' => 'labarugi',
        'piutang' => 'piutang', 'hutang' => 'hutang', 'stok' => 'stok',
        'bulanan' => 'bulanan', 'tahunan' => 'tahunan',
    ];

    public function run(): void
    {
        $page = 'dashboard';
        $action = 'index';
        $id = null;
        $extra = [];

        $pathInfo = current_route_path();
        if ($pathInfo !== '') {
            $segs = array_values(array_filter(explode('/', $pathInfo)));
            $rest = [];

            if (isset($segs[0]) && $segs[0] === 'transaksi' && isset($segs[1])) {
                $page = self::GROUP_TRANSAKSI[$segs[1]] ?? $segs[1];
                $rest = array_slice($segs, 2);
            } elseif (isset($segs[0]) && $segs[0] === 'keuangan' && isset($segs[1])) {
                if ($segs[1] === 'saldo-awal') {
                    $page = 'kas';
                    $extra['tab'] = 'saldo';
                    $rest = array_slice($segs, 2);
                } else {
                    $page = self::GROUP_KEUANGAN[$segs[1]] ?? $segs[1];
                    $rest = array_slice($segs, 2);
                    if ($page === 'kas' && ($rest[0] ?? '') === 'saldo') {
                        $extra['tab'] = 'saldo';
                        $rest = array_slice($rest, 1);
                    }
                }
            } elseif (isset($segs[0]) && $segs[0] === 'master' && isset($segs[1])) {
                $page = self::GROUP_MASTER[$segs[1]] ?? $segs[1];
                $rest = array_slice($segs, 2);
            } elseif (isset($segs[0]) && $segs[0] === 'laporan') {
                $page = 'laporan';
                $extra['tab'] = isset($segs[1]) ? (self::LAPORAN_TAB[$segs[1]] ?? $segs[1]) : 'kas';
                $rest = array_slice($segs, 2);
            } else {
                $page = $segs[0];
                $rest = array_slice($segs, 1);
            }

            // Parsing action/id dari sisa segmen
            if (!empty($rest)) {
                $last = array_pop($rest);
                if (ctype_digit((string)$last)) {
                    $id = $last;
                    if (!empty($rest)) {
                        $action = array_pop($rest);
                    }
                } else {
                    $action = $last;
                }
            } else {
                $action = 'index';
            }
        }

        // Fallback query lama (index.php?page=...&action=...)
        $page = $_GET['page'] ?? $page;
        $action = $_GET['action'] ?? $action;
        $id = $_GET['id'] ?? $id;
        // tab diteruskan melalui query bersih (/laporan/kas, /keuangan/kas/saldo)
        $_GET['tab'] = $_GET['tab'] ?? $extra['tab'] ?? null;

        $controller = $this->resolveController($page);

        // Fallback: aksi sama dengan nama halaman (mis. /login -> AuthController::login)
        if ($action === 'index' && method_exists($controller, $page)) {
            $action = $page;
        }

        $method = $this->resolveAction($controller, $action, $page);
        $params = $this->buildParams($controller, $method, $page, $action, $id);

        $instance = new $controller();
        call_user_func_array([$instance, $method], $params);
    }

    private function resolveController(string $page): string
    {
        $page = strtolower(trim($page));
        $page = $page === '' ? 'dashboard' : $page;

        $map = [
            'login' => 'AuthController',
            'logout' => 'AuthController',
            'password' => 'AuthController',
            'dashboard' => 'DashboardController',
            'barang' => 'BarangController',
            'kategori' => 'KategoriController',
            'supplier' => 'SupplierController',
            'pelanggan' => 'PelangganController',
            'penjualan' => 'PenjualanController',
            'pembelian' => 'PembelianController',
            'pemasukan' => 'PemasukanController',
            'pengeluaran' => 'PengeluaranController',
            'kas' => 'KasController',
            'piutang' => 'PiutangController',
            'hutang' => 'HutangController',
            'modal' => 'ModalController',
            'laporan' => 'LaporanController',
            'profil' => 'ProfilKoperasiController',
            'pengurus' => 'PengurusController',
            'pengguna' => 'PenggunaController',
            'pengaturan' => 'PengaturanController',
            'backup' => 'BackupController',
            'log' => 'LogController',
            'transaksi' => 'TransaksiController',
        ];

        $class = $map[$page] ?? null;
        $file = APP_ROOT . '/app/controllers/' . $class . '.php';
        if ($class === null || !file_exists($file)) {
            abort_notfound();
        }
        require_once $file;
        return $class;
    }

    private function resolveAction(string $controller, string $action, string $page): string
    {
        $allowed = [
            'index', 'create', 'store', 'edit', 'update', 'show', 'detail',
            'cancel', 'destroy', 'delete', 'active', 'inactive', 'login', 'logout',
            'password', 'bayar', 'export', 'print', 'rekap', 'history', 'restore',
            'change_password', 'change', 'download', 'view', 'upload', 'stok',
            'adjust', 'delete_att', 'pembayaran', 'penyesuaian', 'struk', 'delete_many',
        ];
        if (!in_array($action, $allowed, true) || !method_exists($controller, $action)) {
            abort_notfound();
        }
        return $action;
    }

    private function buildParams(string $controller, string $method, string $page, string $action, ?string $id): array
    {
        $params = [];
        $signature = new ReflectionMethod($controller, $method);
        foreach ($signature->getParameters() as $param) {
            if ($param->getName() === 'id') {
                $params[] = $id;
            } else {
                $params[] = null;
            }
        }
        return $params;
    }
}