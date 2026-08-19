<?php
/** Layout utama aplikasi: Sidebar + Topbar + Content */
$user = current_user();
$profile = koperasi_profile();
$currentPage = $_GET['page'] ?? 'dashboard';
$currentTab = $_GET['tab'] ?? '';
$routePath = current_route_path();
if ($routePath !== '') {
    $segs = array_values(array_filter(explode('/', $routePath)));
    if (!empty($segs[0])) {
        if (in_array($segs[0], ['transaksi', 'keuangan', 'master'], true) && isset($segs[1])) {
            $currentPage = $segs[0] === 'keuangan' && $segs[1] === 'saldo-awal' ? 'kas' : $segs[1];
            if ($segs[0] === 'keuangan' && $segs[1] === 'kas' && ($segs[2] ?? '') === 'saldo') {
                $currentTab = 'saldo';
            }
            if ($segs[0] === 'keuangan' && $segs[1] === 'saldo-awal') {
                $currentTab = 'saldo';
            }
            $currentAction = isset($segs[2]) && !ctype_digit($segs[2]) ? $segs[2] : 'index';
        } elseif ($segs[0] === 'laporan') {
            $currentPage = 'laporan';
            $currentTab = isset($segs[1]) ? ($segs[1] === 'laba-rugi' ? 'labarugi' : $segs[1]) : 'kas';
            $currentAction = isset($segs[2]) && !ctype_digit($segs[2]) ? $segs[2] : 'index';
        } else {
            $currentPage = $segs[0];
            $currentAction = isset($segs[1]) ? $segs[1] : 'index';
        }
    }
}
$currentAction = $_GET['action'] ?? $currentAction;

$navGroups = [
    'Ringkasan' => [
        ['dashboard', 'dashboard', 'dashboard', 'Dashboard'],
    ],
    'Transaksi' => [
        ['penjualan', 'penjualan', 'cart', 'Penjualan'],
        ['pembelian', 'pembelian', 'package', 'Pembelian'],
        ['pemasukan', 'pemasukan', 'trending-up', 'Pemasukan Lain'],
        ['pengeluaran', 'pengeluaran', 'trending-down', 'Pengeluaran'],
        ['piutang', 'piutang', 'wallet', 'Piutang'],
        ['hutang', 'hutang', 'bank', 'Hutang'],
        ['kas', 'kas', 'cash', 'Buku Kas'],
        ['transaksi', 'transaksi', 'history', 'Riwayat Transaksi'],
    ],
    'Barang' => [
        ['barang', 'barang', 'box', 'Data Barang'],
        ['kategori', 'kategori', 'tag', 'Kategori'],
        ['supplier', 'supplier', 'truck', 'Supplier'],
        ['pelanggan', 'pelanggan', 'account-group', 'Pelanggan'],
    ],
    'Keuangan' => [
        ['kas', 'kas&tab=saldo', 'scale', 'Saldo Awal'],
        ['modal', 'modal', 'safe', 'Modal Koperasi'],
    ],
    'Laporan' => [
        ['laporan', 'laporan&tab=kas', 'file-chart', 'Laporan Kas'],
        ['laporan', 'laporan&tab=penjualan', 'receipt', 'Laporan Penjualan'],
        ['laporan', 'laporan&tab=pembelian', 'truck-fast', 'Laporan Pembelian'],
        ['laporan', 'laporan&tab=pemasukan', 'chart-line', 'Laporan Pemasukan'],
        ['laporan', 'laporan&tab=pengeluaran', 'chart-arc', 'Laporan Pengeluaran'],
        ['laporan', 'laporan&tab=labarugi', 'scale-balance', 'Laba/Rugi'],
        ['laporan', 'laporan&tab=piutang', 'wallet', 'Laporan Piutang'],
        ['laporan', 'laporan&tab=hutang', 'bank-transfer', 'Laporan Hutang'],
        ['laporan', 'laporan&tab=stok', 'inventory', 'Laporan Stok'],
        ['laporan', 'laporan&tab=bulanan', 'calendar-month', 'Rekap Bulanan'],
        ['laporan', 'laporan&tab=tahunan', 'calendar-range', 'Rekap Tahunan'],
    ],
];

$navAdmin = [
    'Master Data' => [
        ['profil', 'profil', 'school', 'Profil Koperasi'],
        ['pengurus', 'pengurus', 'account-tie', 'Pengurus'],
        ['pengguna', 'pengguna', 'account-cog', 'Pengguna'],
    ],
    'Pengaturan' => [
        ['pengaturan', 'pengaturan', 'cog', 'Pengaturan'],
        ['backup', 'backup', 'database', 'Backup / Restore'],
        ['log', 'log', 'history', 'Log Aktivitas'],
    ],
];
if (has_role('Administrator')) {
    $navGroups = array_merge($navGroups, $navAdmin);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(isset($pageTitle) ? $pageTitle . ' - ' : '') ?><?= e($profile['nama_koperasi'] ?? APP_NAME) ?></title>
<script src="<?= asset('assets/js/tailwind.min.js') ?>"></script>
<link rel="stylesheet" href="<?= asset('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="bg-slate-100 text-slate-800 flex flex-col min-h-screen">

<!-- ======================= SIDEBAR ======================= -->
<div id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col">
    <!-- Sticky Header -->
    <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-800 shrink-0 sticky top-0 bg-slate-900 z-10">
        <?php if (!empty($profile['logo']) && file_exists(UPLOAD_DIR . '/' . $profile['logo'])): ?>
            <img src="<?= asset('uploads/' . e($profile['logo'])) ?>" class="h-10 w-10 rounded-full object-cover" alt="logo">
        <?php else: ?>
            <div class="h-10 w-10 rounded-full bg-emerald-600 flex items-center justify-center font-bold text-white"><?= e(mb_substr($profile['nama_koperasi'] ?? 'KS', 0, 2)) ?></div>
        <?php endif; ?>
        <div>
            <div class="font-semibold text-sm leading-tight"><?= e($profile['nama_koperasi'] ?? APP_NAME) ?></div>
            <div class="text-xs text-slate-400 truncate"><?= e($profile['nama_sekolah'] ?? '') ?></div>
        </div>
    </div>

    <!-- Scrollable Navigation -->
    <nav class="py-3 overflow-y-auto flex-1">
        <?php foreach ($navGroups as $group => $items): ?>
            <div class="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500"><?= e($group) ?></div>
            <?php foreach ($items as $item): [$page, $href, $icon, $label] = $item; ?>
                <?php
                $active = false;
                $navAction = null;
                $tab = null;
                $hrefParts = explode('&', $href);
                $hrefPage = $hrefParts[0];
                $hrefParams = [];
                if (isset($hrefParts[1])) {
                    if (strpos($hrefParts[1], 'action=') === 0) {
                        $navAction = str_replace('action=', '', $hrefParts[1]);
                        $hrefParams['action'] = $navAction;
                    } else {
                        $tab = str_replace('tab=', '', $hrefParts[1]);
                        $hrefParams['tab'] = $tab;
                    }
                }
                if ($hrefParts[0] === $currentPage) {
                    if ($navAction !== null) {
                        $active = ($currentAction === $navAction);
                    } elseif ($tab !== null) {
                        $active = ($currentTab !== '' && $currentTab === $tab);
                    } else {
                        $active = ($currentTab === '' && $currentAction !== 'history');
                    }
                }
                ?>
                <a href="<?= url($hrefPage, $hrefParams) ?>"
                   class="flex items-center gap-3 mx-2 my-0.5 px-3 py-2 rounded-lg text-sm <?= $active ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <?= icon($icon) ?>
                    <span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <div class="px-4 pt-3 pb-1 mt-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Akun</div>
        <a href="<?= url('password') ?>" class="flex items-center gap-3 mx-2 my-0.5 px-3 py-2 rounded-lg text-sm hover:bg-slate-800 hover:text-white">
            <?= icon('key') ?><span>Ganti Password</span>
        </a>
        <a href="<?= url('logout') ?>" class="flex items-center gap-3 mx-2 my-0.5 px-3 py-2 rounded-lg text-sm hover:bg-slate-800 hover:text-white">
            <?= icon('logout') ?><span>Keluar</span>
        </a>
    </nav>
</div>
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>

<!-- ======================= MAIN ======================= -->
<div class="lg:pl-64 flex flex-col flex-1 min-h-0">
    <!-- Topbar -->
    <header class="sticky top-0 z-20 bg-white border-b border-slate-200 px-4 sm:px-6 h-16 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <button id="sidebarToggle" class="lg:hidden text-slate-500 hover:text-slate-800 p-1"><?= icon('menu', 'w-6 h-6') ?></button>
            <h1 class="text-lg sm:text-xl font-semibold text-slate-800"><?= e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <div class="text-sm font-medium"><?= e($user['name']) ?></div>
                <div class="text-xs text-slate-500"><?= e($user['role_name']) ?></div>
            </div>
            <div class="h-10 w-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold">
                <?= e(strtoupper(mb_substr($user['name'], 0, 2))) ?>
            </div>
        </div>
    </header>

<!-- Content -->
    <main class="flex-1 p-4 sm:p-6 pb-12">
        <?= flash_swal_scripts() ?>

        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-50 border-t border-slate-200 lg:pl-64">
        <div class="max-w-full mx-auto px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-2 text-xs text-slate-500">
                <span>Sistem Informasi Koperasi Madrasah - <?= e($profile['nama_sekolah'] ?? 'Nama Sekolah') ?></span>
                <span>&copy; <?= date('Y') ?> All rights reserved.</span>
            </div>
        </div>
    </footer>
</div>
        </div>
    </footer>
</div>

<script src="<?= asset('assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<?php if (isset($pageScripts)): foreach ((array)$pageScripts as $s): ?>
<script src="<?= asset('assets/js/' . $s) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
