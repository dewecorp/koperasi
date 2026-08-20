<?php
/** Halaman login */
$profile = koperasi_profile();
$logo = '';
if (!empty($profile['logo']) && file_exists(UPLOAD_DIR . '/' . $profile['logo'])) {
    $logo = '<img src="' . asset('uploads/' . e($profile['logo'])) . '" class="max-h-14 w-auto object-contain" alt="logo">';
} else {
    $logo = '<div class="h-12 w-12 rounded-full bg-white/20 ring-2 ring-white/60 flex items-center justify-center text-white font-bold text-lg">' . e(mb_substr($profile['nama_koperasi'] ?? 'KS', 0, 2)) . '</div>';
}
$check = '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>';
$fitur = [
    'Pencatatan kas & transaksi harian',
    'Manajemen stok dengan barcode scanner',
    'Piutang, hutang & modal koperasi',
    'Laporan keuangan bulanan & tahunan',
];
?>
<div class="w-full max-w-5xl">
    <div class="grid md:grid-cols-2 bg-white rounded-3xl shadow-2xl overflow-hidden">

        <!-- Panel Informasi (desktop) -->
        <div class="hidden md:flex flex-col justify-between bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 text-white p-10">
            <div>
                <div class="flex items-center gap-3 mb-10">
                    <?= $logo ?>
                    <span class="font-bold text-lg"><?= e($profile['nama_koperasi'] ?? APP_NAME) ?></span>
                </div>
                <h2 class="text-2xl xl:text-3xl font-bold leading-snug">Sistem Informasi<br>Koperasi Madrasah</h2>
                <p class="mt-3 text-emerald-100 text-sm leading-relaxed">Kelola keuangan koperasi sekolah secara digital: kas, persediaan barang, penjualan, piutang, hutang hingga laporan tahunan dalam satu aplikasi.</p>
                <ul class="mt-7 space-y-3">
                    <?php foreach ($fitur as $f): ?>
                        <li class="flex items-start gap-2.5 text-sm text-emerald-50">
                            <span class="mt-0.5 text-emerald-300"><?= $check ?></span>
                            <span><?= e($f) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <div class="border-t border-white/20 pt-4 flex items-center justify-between text-xs text-emerald-200">
                    <span><?= e($profile['nama_sekolah'] ?? APP_NAME) ?></span>
                    <span>&copy; <?= date('Y') ?></span>
                </div>
            </div>
        </div>

        <!-- Panel Form Login -->
        <div class="p-8 md:p-12">
            <div class="text-center mb-8 md:hidden">
                <div class="flex justify-center mb-3"><?= $logo ?></div>
                <h1 class="text-xl font-bold text-slate-800"><?= e($profile['nama_koperasi'] ?? APP_NAME) ?></h1>
                <p class="text-sm font-medium text-emerald-600 mt-0.5">Sistem Informasi Koperasi Madrasah</p>
                <p class="text-sm text-slate-500 uppercase tracking-wide"><?= e($profile['nama_sekolah'] ?? '') ?></p>
            </div>

            <div class="hidden md:block mb-8">
                <h1 class="text-2xl font-bold text-slate-800">Selamat Datang</h1>
                <p class="text-sm text-slate-500 mt-1">Masuk untuk mengelola koperasi</p>
            </div>

            <form method="post" action="<?= url('login') ?>">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label class="label">Username</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                        </span>
                        <input type="text" name="username" class="input pl-10" autocomplete="username" required autofocus placeholder="Masukkan username" value="<?= e(old('username')) ?>">
                    </div>
                </div>
                <div class="mb-6">
                    <label class="label">Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" name="password" class="input pl-10" autocomplete="current-password" required placeholder="Masukkan password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center py-2.5 text-base">Masuk</button>
            </form>

            <p class="text-center text-xs text-slate-400 mt-6">Sistem Informasi Koperasi Madrasah &copy; <?= date('Y') ?></p>
        </div>
    </div>
</div>