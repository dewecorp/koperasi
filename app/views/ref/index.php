<?php
$pageKey = $refLabel;
$editId = (int)input('edit', 0);
$editing = null;
if ($editId) {
    foreach ($pg['items'] as $row) {
        if ((int)$row['id'] === $editId) { $editing = $row; break; }
    }
    if (!$editing) {
        $stmt = db()->prepare('SELECT * FROM ' . ($pageKey === 'supplier' ? 'suppliers' : 'customers') . ' WHERE id = ?');
        $stmt->execute([$editId]);
        $editing = $stmt->fetch() ?: null;
    }
}
?>
<div class="max-w-5xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
            <form method="get" action="<?= url($pageKey) ?>" class="js-filter-form flex gap-2 flex-1 max-w-md">
                <input type="hidden" name="page" value="<?= e($pageKey) ?>">
                <div class="relative flex-1">
                    <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                    <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nama / telepon...">
                </div>
            </form>
            <a href="<?= url($pageKey) ?>" class="btn btn-primary" onclick="document.getElementById('refFormWrap').scrollIntoView({behavior:'smooth'}); return false;"><?= icon('plus', 'w-4 h-4') ?> Tambah <?= e(ucfirst($refLabel)) ?></a>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr>
                    <?php foreach ($fields as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
                    <th>Status</th><th class="text-center">Aksi</th>
                </tr></thead>
                <tbody>
                <?php if (empty($pg['items'])): ?>
                    <tr><td colspan="<?= count($fields) + 2 ?>" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
                <?php else: foreach ($pg['items'] as $row): ?>
                    <tr>
                        <?php foreach (array_keys($fields) as $key): ?>
                            <td><?= e($row[$key] ?? '-') ?></td>
                        <?php endforeach; ?>
                        <td>
                            <?php if ((int)$row['is_active'] === 1): ?>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">Aktif</span>
                            <?php else: ?>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 font-medium">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="<?= url($pageKey, ['q' => $q, 'edit' => $row['id']]) ?>" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></a>
                                <form method="post" action="<?= url($pageKey, ['action' => 'destroy', 'id' => $row['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5" onclick="return appConfirmSubmit(event, 'Hapus data ini?')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
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

    <div id="refFormWrap" class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-3"><?= $editId ? 'Ubah ' . ucfirst($refLabel) : 'Tambah ' . ucfirst($refLabel) ?></h2>
        <form method="post" action="<?= $editId ? url($pageKey, ['action' => 'update', 'id' => $editId]) : url($pageKey, ['action' => 'store']) ?>" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?= csrf_field() ?>
            <div>
                <label class="label">Nama *</label>
                <input type="text" name="name" class="input" value="<?= e($editing['name'] ?? old('name')) ?>" required>
            </div>
            <div>
                <label class="label">Telepon</label>
                <input type="text" name="phone" class="input" value="<?= e($editing['phone'] ?? old('phone')) ?>">
            </div>
            <div class="sm:col-span-3">
                <label class="label">Alamat</label>
                <textarea name="address" class="input" rows="2"><?= e($editing['address'] ?? old('address')) ?></textarea>
            </div>
            <div class="sm:col-span-3 flex justify-end gap-2">
                <?php if ($editId): ?><a href="<?= url($pageKey, ['q' => $q]) ?>" class="btn btn-secondary">Batal</a><?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>
