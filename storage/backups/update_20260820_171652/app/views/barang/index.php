<?php $canEdit = has_role('Administrator') || has_role('Bendahara'); ?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('barang') ?>" class="js-filter-form flex flex-col sm:flex-row gap-2 flex-1 max-w-2xl">
            <input type="hidden" name="page" value="barang">
            <div class="relative flex-1">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari kode / nama / barcode...">
            </div>
            <select name="cat" class="input w-full sm:w-44">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategori as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $cat == $k['id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="input w-full sm:w-36">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </form>
        <?php if ($canEdit): ?>
            <a href="<?= url('barang', ['action' => 'create']) ?>" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Tambah Barang</a>
        <?php endif; ?>
        <?php if (has_role('Administrator')): ?>
            <form method="post" action="<?= url('barang', ['action' => 'delete_many']) ?>" id="bulkForm">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger" onclick="return appConfirmSubmit(event, 'Barang yang pernah dipakai transaksi tidak dapat dihapus, hanya dinonaktifkan. Lanjutkan menghapus barang terpilih?', 'Hapus Barang Terpilih')"><?= icon('trash', 'w-4 h-4') ?> Hapus Terpilih</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr>
                <?php if (has_role('Administrator')): ?>
                    <th class="w-10"><input type="checkbox" id="checkAll" class="accent-emerald-600" title="Pilih semua"></th>
                <?php endif; ?>
                <th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Satuan</th>
                <th class="text-right">Harga Beli</th><th class="text-right">Harga Jual</th>
                <th class="text-right">Stok</th><th class="text-right">Min.</th><th>Status</th><th class="text-center">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="<?= has_role('Administrator') ? 11 : 10 ?>" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
            <?php else: foreach ($pg['items'] as $p): ?>
                <tr>
                    <?php if (has_role('Administrator')): ?>
                        <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" class="row-check accent-emerald-600" form="bulkForm"></td>
                    <?php endif; ?>
                    <td class="font-mono text-xs whitespace-nowrap"><?= e($p['kode']) ?></td>
                    <td class="font-medium"><?= e($p['name']) ?></td>
                    <td><?= e($p['kategori'] ?? '-') ?></td>
                    <td><?= e($p['satuan']) ?></td>
                    <td class="text-right whitespace-nowrap"><?= rupiah($p['harga_beli']) ?></td>
                    <td class="text-right whitespace-nowrap"><?= rupiah($p['harga_jual']) ?></td>
                    <td class="text-right font-semibold <?= (float)$p['stock'] <= (float)$p['stock_minimum'] ? 'text-red-600' : 'text-slate-800' ?>"><?= angka($p['stock']) ?></td>
                    <td class="text-right text-slate-500"><?= angka($p['stock_minimum']) ?></td>
                    <td>
                        <?php if ((int)$p['is_active'] === 1): ?>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">Aktif</span>
                        <?php else: ?>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 font-medium">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= url('barang', ['action' => 'show', 'id' => $p['id']]) ?>" title="Detail & Kartu Stok" class="btn btn-ghost p-1.5"><?= icon('eye', 'w-4 h-4') ?></a>
                            <?php if ($canEdit): ?>
                                <a href="<?= url('barang', ['action' => 'edit', 'id' => $p['id']]) ?>" title="Ubah" class="btn btn-ghost p-1.5"><?= icon('edit', 'w-4 h-4') ?></a>
                                <a href="<?= url('barang', ['action' => 'adjust', 'id' => $p['id']]) ?>" title="Penyesuaian Stok" class="btn btn-ghost p-1.5"><?= icon('sliders', 'w-4 h-4') ?></a>
                            <?php endif; ?>
                            <?php if (has_role('Administrator')): ?>
                                <form method="post" action="<?= url('barang', ['action' => 'active', 'id' => $p['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="val" value="<?= (int)$p['is_active'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-ghost p-1.5" title="<?= (int)$p['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                        <?= icon((int)$p['is_active'] === 1 ? 'x' : 'check', 'w-4 h-4') ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= url('barang', ['action' => 'destroy', 'id' => $p['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" title="Hapus" onclick="return appConfirmSubmit(event, 'Barang yang pernah dipakai dalam transaksi tidak dapat dihapus, hanya dinonaktifkan. Lanjutkan?', 'Hapus Barang')"><?= icon('trash', 'w-4 h-4') ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_links($pg) ?>
</div>

<?php if (has_role('Administrator')): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkAll = document.getElementById('checkAll');
    if (!checkAll) return;
    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(function (c) {
            c.checked = checkAll.checked;
        });
    });
});
</script>
<?php endif; ?>
