<?php
$isEdit = $barang !== null;
$old = function (string $key, $default = '') use ($barang) {
    return old($key, $barang[$key] ?? $default);
};
?>
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form method="post" action="<?= url('barang', ['action' => 'store', 'id' => $isEdit ? $barang['id'] : null]) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <div>
                <label class="label">Kode Barang *</label>
                <input type="text" name="kode" class="input" value="<?= e($old('kode')) ?>" required>
            </div>
            <div>
                <label class="label">Barcode</label>
                <input type="text" name="barcode" class="input" value="<?= e($old('barcode')) ?>">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Nama Barang *</label>
                <input type="text" name="name" class="input" value="<?= e($old('name')) ?>" required>
            </div>
            <div>
                <label class="label">Kategori</label>
                <select name="category_id" class="input">
                    <option value="">- Pilih -</option>
                    <?php foreach ($kategori as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= (string)($barang['category_id'] ?? '') === (string)$k['id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Satuan *</label>
                <input type="text" name="satuan" class="input" value="<?= e($old('satuan')) ?>" placeholder="pcs" required>
            </div>
            <div>
                <label class="label">Harga Beli *</label>
                <input type="number" name="harga_beli" class="input" min="0" step="0.01" value="<?= e($old('harga_beli')) ?>" required>
            </div>
            <div>
                <label class="label">Harga Jual *</label>
                <input type="number" name="harga_jual" class="input" min="0" step="0.01" value="<?= e($old('harga_jual')) ?>" required>
            </div>
            <div>
                <label class="label">Stok Awal</label>
                <input type="number" name="stock_awal" class="input" min="0" step="0.01" value="<?= e(old('stock_awal')) ?>" <?= $isEdit ? 'disabled title="Ubah stok lewat transaksi / penyesuaian stok"' : '' ?>>
                <?php if ($isEdit): ?>
                    <p class="text-xs text-slate-400 mt-1">Stok dikelola lewat pembelian, penjualan, atau penyesuaian.</p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label">Stok Minimum *</label>
                <input type="number" name="stock_minimum" class="input" min="0" step="0.01" value="<?= e($old('stock_minimum')) ?>" required>
            </div>
            <div>
                <label class="label">Supplier</label>
                <select name="supplier_id" class="input">
                    <option value="">- Pilih -</option>
                    <?php foreach ($supplier as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (string)($barang['supplier_id'] ?? '') === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Status</label>
                <select name="is_active" class="input">
                    <option value="1" <?= ($barang['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= ($barang['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
                <a href="<?= url('barang') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>
