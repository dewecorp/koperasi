<?php $p = $profile; ?>
<div class="w-full">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4 mb-6">
            <?php if (!empty($p['logo']) && file_exists(UPLOAD_DIR . '/' . $p['logo'])): ?>
                <img src="<?= asset('uploads/' . e($p['logo'])) ?>" class="h-20 w-20 rounded-2xl object-cover" alt="logo">
            <?php else: ?>
                <div class="h-20 w-20 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl font-bold"><?= e(mb_substr($p['nama_koperasi'] ?? 'KS', 0, 2)) ?></div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <h2 class="font-semibold text-slate-800 truncate"><?= e($p['nama_koperasi'] ?? '-') ?></h2>
                <p class="text-sm text-slate-500 truncate"><?= e($p['nama_sekolah'] ?? '') ?></p>
                <p class="text-xs text-slate-400 truncate"><?= e($p['alamat'] ?? '') ?> <?= e($p['telepon'] ?? '') !== '' ? '· ' . e($p['telepon']) : '' ?></p>
            </div>
            <button type="button" onclick="openProfilModal()" class="btn btn-primary shrink-0"><?= icon('edit', 'w-4 h-4') ?> Ubah Profil</button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Nama Koperasi</div><div class="font-medium text-slate-800 mt-1"><?= e($p['nama_koperasi'] ?? '-') ?></div></div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Nama Sekolah</div><div class="font-medium text-slate-800 mt-1"><?= e($p['nama_sekolah'] ?? '-') ?></div></div>
            <div class="sm:col-span-2 p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Alamat</div><div class="font-medium text-slate-800 mt-1"><?= e($p['alamat'] ?? '-') ?></div></div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Telepon</div><div class="font-medium text-slate-800 mt-1"><?= e($p['telepon'] ?? '-') ?></div></div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Email</div><div class="font-medium text-slate-800 mt-1"><?= e($p['email'] ?? '-') ?></div></div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Nama Ketua</div><div class="font-medium text-slate-800 mt-1"><?= e($p['nama_ketua'] ?? '-') ?></div></div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Nama Bendahara</div><div class="font-medium text-slate-800 mt-1"><?= e($p['nama_bendahara'] ?? '-') ?></div></div>
        </div>
    </div>
</div>

<div id="profilModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeProfilModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800">Ubah Profil Koperasi</h3>
                <button type="button" onclick="closeProfilModal()" class="h-8 w-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500">&times;</button>
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
                <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeProfilModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openProfilModal(){ document.getElementById('profilModal').classList.remove('hidden'); }
function closeProfilModal(){ document.getElementById('profilModal').classList.add('hidden'); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeProfilModal(); });
</script>
<noscript>
<div class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <form method="post" action="<?= url('profil') ?>" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <div><label class="label">Nama Koperasi *</label><input type="text" name="nama_koperasi" class="input" value="<?= e($p['nama_koperasi']) ?>" required></div>
        <div><label class="label">Nama Sekolah</label><input type="text" name="nama_sekolah" class="input" value="<?= e($p['nama_sekolah']) ?>"></div>
        <div class="sm:col-span-2"><label class="label">Alamat</label><textarea name="alamat" class="input" rows="2"><?= e($p['alamat']) ?></textarea></div>
        <div><label class="label">Telepon</label><input type="text" name="telepon" class="input" value="<?= e($p['telepon']) ?>"></div>
        <div><label class="label">Email</label><input type="email" name="email" class="input" value="<?= e($p['email']) ?>"></div>
        <div class="sm:col-span-2"><label class="label">Logo</label><input type="file" name="logo" class="input"></div>
        <div><label class="label">Nama Ketua</label><input type="text" name="nama_ketua" class="input" value="<?= e($p['nama_ketua']) ?>"></div>
        <div><label class="label">Nama Bendahara</label><input type="text" name="nama_bendahara" class="input" value="<?= e($p['nama_bendahara']) ?>"></div>
        <div class="sm:col-span-2 flex justify-end"><button type="submit" class="btn btn-primary">Simpan</button></div>
    </form>
</div>
</noscript>
