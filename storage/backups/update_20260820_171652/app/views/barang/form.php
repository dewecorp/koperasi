<?php
$isEdit = $barang !== null;
$old = function (string $key, $default = '') use ($barang) {
    return old($key, $barang[$key] ?? $default);
};
$scanUrl = url('barang', ['action' => 'cariBarcode']);
?>
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form method="post" action="<?= url('barang', ['action' => 'store', 'id' => $isEdit ? $barang['id'] : null]) ?>" id="formBarang" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <div>
                <label class="label">Kode Barang<?= $isEdit ? '' : ' (otomatis)' ?> *</label>
                <input type="text" name="kode" class="input" value="<?= e($old('kode')) ?>" placeholder="<?= $isEdit ? '' : 'Kosongkan = otomatis' ?>" <?= $isEdit ? 'required' : '' ?>>
                <?php if (!$isEdit): ?><p class="text-xs text-slate-400 mt-1">Biarkan kosong untuk kode otomatis (BRG00001).</p><?php endif; ?>
            </div>
            <div>
                <label class="label">Barcode<?= $isEdit ? '' : ' (otomatis)' ?></label>
                <input type="text" name="barcode" id="inputBarcode" class="input" value="<?= e($old('barcode')) ?>" placeholder="<?= $isEdit ? '' : 'Scan / biarkan kosong = otomatis' ?>" autofocus>
                <p class="text-xs text-slate-400 mt-1"><?= $isEdit ? 'Barcode tetap disimpan. Scanner otomatis mencari barang yang sudah ada.' : 'Scan barcode: jika barang sudah ada, langsung dibuka untuk diubah. Jika belum ada, form ini mengisinya.' ?></p>
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
                <?php
                $daftarSatuan = ['pcs', 'buah', 'butir', 'biji', 'lusin', 'kodi', 'rim', 'pak', 'pack', 'set', 'box', 'kardus', 'botol', 'sachet', 'kg', 'gram', 'ons', 'liter', 'ml', 'galon', 'meter', 'cm', 'lembar', 'pasang', 'unit'];
                $satuanAktif = trim((string)$old('satuan')) !== '' ? trim((string)$old('satuan')) : 'pcs';
                if (!in_array($satuanAktif, $daftarSatuan, true)) {
                    $daftarSatuan[] = $satuanAktif;
                }
                ?>
                <select name="satuan" class="input" required>
                    <?php foreach ($daftarSatuan as $s): ?>
                        <option value="<?= e($s) ?>" <?= $s === $satuanAktif ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Harga Beli *</label>
                <input type="text" name="harga_beli" class="input" inputmode="numeric" min="0" step="0.01" value="<?= e($old('harga_beli')) ?>" required>
            </div>
            <div>
                <label class="label">Harga Jual *</label>
                <input type="text" name="harga_jual" class="input" inputmode="numeric" min="0" step="0.01" value="<?= e($old('harga_jual')) ?>" required>
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

<script>
(function () {
    var inputBarcode = document.getElementById('inputBarcode');
    var isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    var scanUrl = <?= json_encode($scanUrl) ?>;
    var userTyped = false;

    // Deteksi input dari scanner (kirim cepat + Enter) vs ketikan manual.
    var typingTimer = null;
    inputBarcode.addEventListener('keydown', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function () { userTyped = true; }, 500);
    });

    inputBarcode.addEventListener('change', function () {
        var v = this.value.trim();
        if (v === '') return;
        var isScan = !userTyped || v.length >= 8;
        userTyped = false;
        if (!isScan) return;

        fetch(scanUrl + '?barcode=' + encodeURIComponent(v))
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.found) {
                    var p = res.produk;
                    Swal.fire({
                        icon: 'info',
                        title: 'Barang sudah ada',
                        text: p.kode + ' - ' + p.name + ' (stok ' + p.stock + ')',
                        showCancelButton: true,
                        confirmButtonText: 'Buka & Ubah',
                        cancelButtonText: 'Tetap Baru',
                        confirmButtonColor: '#059669'
                    }).then(function (r) {
                        if (r.isConfirmed) {
                            window.location.href = '<?= url('barang', ['action' => 'edit']) ?>/' + p.id;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Barang baru',
                        text: 'Barcode tidak ditemukan. Silakan lengkapi data barang baru.',
                        timer: 2500,
                        showConfirmButton: false
                    });
                    document.querySelector('input[name="name"]').focus();
                }
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat memeriksa barcode.', confirmButtonText: 'OK' });
            });
    });

    // Saat edit, isi nama otomatis disarankan fokus normal.
    inputBarcode.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') e.preventDefault();
    });
})();
</script>
