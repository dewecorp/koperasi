<?php
$pdo = db();
$editId = (int)input('edit', 0);
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}
?>
<div class="max-w-5xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
            <form method="get" action="<?= url('pengguna') ?>" class="js-filter-form flex gap-2 flex-1 max-w-md">
                <input type="hidden" name="page" value="pengguna">
                <div class="relative flex-1">
                    <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                    <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari username / nama...">
                </div>
            </form>
            <a href="#formPengguna" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Tambah Pengguna</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Username</th><th>Nama</th><th>Peran</th><th>Password Awal</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($pg['items'])): ?>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
                <?php else: foreach ($pg['items'] as $u): ?>
                    <tr>
                        <td class="font-mono text-xs"><?= e($u['username']) ?></td>
                        <td><?= e($u['name']) ?></td>
                        <td>
                            <span class="text-[11px] px-2 py-0.5 rounded-full <?= $u['role_name'] === 'Administrator' ? 'bg-fuchsia-100 text-fuchsia-700' : ($u['role_name'] === 'Bendahara' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600') ?> font-medium"><?= e($u['role_name']) ?></span>
                        </td>
                        <td><?= (int)$u['must_change_password'] === 1 ? '<span class="text-amber-600 text-xs font-medium">WAJIB GANTI</span>' : '<span class="text-emerald-600 text-xs">OK</span>' ?></td>
                        <td>
                            <?php if ((int)$u['is_active'] === 1): ?>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">Aktif</span>
                            <?php else: ?>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 font-medium">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="<?= url('pengguna', ['edit' => $u['id']]) ?>" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></a>
                                <form method="post" action="<?= url('pengguna', ['action' => 'destroy', 'id' => $u['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5" onclick="return appConfirmSubmit(event, 'Hapus pengguna <?= e($u['username']) ?>?')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_links($pg) ?>
    </div>

    <div id="formPengguna" class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-3"><?= $editId ? 'Ubah Pengguna' : 'Tambah Pengguna' ?></h2>
        <form method="post" action="<?= $editId ? url('pengguna', ['action' => 'update', 'id' => $editId]) : url('pengguna', ['action' => 'store']) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?= csrf_field() ?>
            <?php if (!$editId): ?>
                <div>
                    <label class="label">Username *</label>
                    <input type="text" name="username" class="input" value="<?= e(old('username')) ?>" required <?= $editId ? 'disabled' : '' ?>>
                </div>
            <?php endif; ?>
            <div>
                <label class="label">Nama Lengkap *</label>
                <input type="text" name="name" class="input" value="<?= e($editing['name'] ?? old('name')) ?>" required>
            </div>
            <div>
                <label class="label">Peran *</label>
                <select name="role_id" class="input">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (string)($editing['role_id'] ?? '') === (string)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Password <?= $editId ? '(kosongkan bila tidak diganti)' : '*' ?></label>
                <input type="password" name="password" class="input" minlength="6" <?= $editId ? '' : 'required' ?>>
            </div>
            <div>
                <label class="label">Status</label>
                <select name="is_active" class="input">
                    <option value="1" <?= ($editing['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= ($editing['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-span-full flex justify-end gap-2 pt-2">
                <?php if ($editId): ?><a href="<?= url('pengguna') ?>" class="btn btn-secondary">Batal</a><?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>