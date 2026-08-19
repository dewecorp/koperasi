<?php $id = (int)input('edit', 0); ?>
<div class="max-w-4xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form method="get" action="<?= url('kategori') ?>" class="js-filter-form flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-5">
            <label class="label !mb-0">Tipe Kategori</label>
            <?php foreach ($types as $key => $label): ?>
                <a href="<?= url('kategori', ['type' => $key]) ?>" class="btn <?= $type === $key ? 'btn-primary' : 'btn-secondary' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h2 class="font-semibold text-slate-800 mb-3"><?= $id ? 'Ubah Kategori' : 'Tambah Kategori' ?></h2>
                <?php
                $editing = null;
                if ($id) {
                    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
                    $stmt->execute([$id]);
                    $editing = $stmt->fetch() ?: null;
                }
                ?>
                <form method="post" action="<?= $id ? url('kategori', ['action' => 'update', 'id' => $id]) : url('kategori', ['action' => 'store']) ?>" class="flex flex-col sm:flex-row gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="<?= e($type) ?>">
                    <input type="text" name="name" class="input" placeholder="Nama kategori..." value="<?= e($editing['name'] ?? old('name')) ?>" required>
                    <button type="submit" class="btn btn-primary whitespace-nowrap"><?= icon('check', 'w-4 h-4') ?> <?= $id ? 'Simpan' : 'Tambah' ?></button>
                    <?php if ($id): ?><a href="<?= url('kategori', ['type' => $type]) ?>" class="btn btn-secondary">Batal</a><?php endif; ?>
                </form>
            </div>

            <div>
                <h2 class="font-semibold text-slate-800 mb-3">Daftar <?= e($types[$type]) ?></h2>
                <div class="table-wrap max-h-96 overflow-y-auto">
                    <table>
                        <thead><tr><th>Nama</th><th class="text-right">Dipakai Barang</th><th class="text-center">Aksi</th></tr></thead>
                        <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="3" class="text-center text-slate-400 py-6">Kosong.</td></tr>
                        <?php else: foreach ($items as $k): ?>
                            <tr>
                                <td><?= e($k['name']) ?></td>
                                <td class="text-right"><?= angka($k['jumlah_barang']) ?></td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="<?= url('kategori', ['type' => $type, 'edit' => $k['id']]) ?>" class="btn btn-ghost p-1.5"><?= icon('edit', 'w-4 h-4') ?></a>
                                        <form method="post" action="<?= url('kategori', ['action' => 'destroy', 'id' => $k['id']]) ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-ghost p-1.5" onclick="return appConfirmSubmit(event, 'Hapus kategori ini?')" title="Hapus">
                                                <?= icon('trash', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
