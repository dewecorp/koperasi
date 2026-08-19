<?php
$p = $profile;
?>
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4 mb-6">
            <?php if (!empty($p['logo']) && file_exists(UPLOAD_DIR . '/' . $p['logo'])): ?>
                <img src="<?= asset('uploads/' . e($p['logo'])) ?>" class="h-20 w-20 rounded-2xl object-cover" alt="logo">
            <?php else: ?>
                <div class="h-20 w-20 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl font-bold">
                    <?= e(mb_substr($p['nama_koperasi'] ?? 'KS', 0, 2)) ?>
                </div>
            <?php endif; ?>
            <div>
                <h2 class="font-semibold text-slate-800"><?= e($p['nama_koperasi'] ?? '-') ?></h2>
                <p class="text-sm text-slate-500"><?= e($p['nama_sekolah'] ?? '') ?></p>
            </div>
        </div>

        <form method="post" action="<?= url('profil') ?>" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <div>
                <label class="label">Nama Koperasi *</label>
                <input type="text" name="nama_koperasi" class="input" value="<?= e($p['nama_koperasi']) ?>" required>
            </div>
            <div>
                <label class="label">Nama Sekolah</label>
                <input type="text" name="nama_sekolah" class="input" value="<?= e($p['nama_sekolah']) ?>">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Alamat</label>
                <textarea name="alamat" class="input" rows="2"><?= e($p['alamat']) ?></textarea>
            </div>
            <div>
                <label class="label">Telepon</label>
                <input type="text" name="telepon" class="input" value="<?= e($p['telepon']) ?>">
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" name="email" class="input" value="<?= e($p['email']) ?>">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Logo</label>
                <input type="file" name="logo" class="input" accept="image/jpeg,image/png,image/gif">
                <p class="text-xs text-slate-400 mt-1">JPG/PNG/GIF, maksimal 2 MB. Kosongkan bila tidak diganti.</p>
            </div>
            <div>
                <label class="label">Nama Ketua</label>
                <input type="text" name="nama_ketua" class="input" value="<?= e($p['nama_ketua']) ?>">
            </div>
            <div>
                <label class="label">Nama Bendahara</label>
                <input type="text" name="nama_bendahara" class="input" value="<?= e($p['nama_bendahara']) ?>">
            </div>
            <div class="sm:col-span-2 flex justify-end pt-2">
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>
