<?php
/** Halaman ganti password */
?>
<div class="w-full">
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="mb-6 text-center">
            <div class="text-4xl mb-2 text-emerald-600 inline-flex"><?= icon('key', 'w-12 h-12') ?></div>
            <h1 class="text-xl font-bold text-slate-800">Ganti Password</h1>
            <p class="text-sm text-slate-500 mt-1">Halo, <?= e($user['name']) ?>! Password default wajib diganti.</p>
        </div>

        <form method="post" action="<?= url('password') ?>" class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <div>
                <label class="label">Password Lama</label>
                <input type="password" name="current_password" class="input" required>
            </div>
            <div>
                <label class="label">Password Baru</label>
                <input type="password" name="new_password" class="input" minlength="6" required>
                <p class="text-xs text-slate-400 mt-1">Minimal 6 karakter.</p>
            </div>
            <div>
                <label class="label">Ulangi Password Baru</label>
                <input type="password" name="confirm_password" class="input" required>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full justify-center py-2.5">Simpan Password</button>
            </div>
        </form>
    </div>
</div>
