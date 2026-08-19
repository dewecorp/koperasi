<?php
/** Halaman login */
$profile = koperasi_profile();
?>
<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="flex items-center justify-center gap-3 mb-6">
            <?php if (!empty($profile['logo']) && file_exists(UPLOAD_DIR . '/' . $profile['logo'])): ?>
                <img src="<?= asset('uploads/' . e($profile['logo'])) ?>" class="h-14 w-14 rounded-xl object-cover" alt="logo">
            <?php else: ?>
                <div class="h-14 w-14 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-xl">
                    <?= e(mb_substr($profile['nama_koperasi'] ?? 'KS', 0, 2)) ?>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="text-xl font-bold text-slate-800"><?= e($profile['nama_koperasi'] ?? APP_NAME) ?></h1>
                <p class="text-sm text-slate-500"><?= e($profile['nama_sekolah'] ?? 'Sistem Pencatatan Keuangan') ?></p>
            </div>
        </div>

        <form method="post" action="<?= url('login') ?>" class="mt-6">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="label">Username</label>
                <input type="text" name="username" class="input" autocomplete="username" required autofocus value="<?= e(old('username')) ?>">
            </div>
            <div class="mb-4">
                <label class="label">Password</label>
                <input type="password" name="password" class="input" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-full justify-center py-2.5">Masuk</button>
        </form>
    </div>
</div>
