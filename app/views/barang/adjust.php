<?php
$stok = (float)$barang['stock'];
$keterangan = trim(input('keterangan', ''));
$jenis = input('jenis', 'masuk');
$qty = input('qty', '');
?>
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-1"><?= e($barang['name']) ?></h2>
        <p class="text-sm text-slate-500 mb-4">Stok saat ini: <b class="<?= $stok <= (float)$barang['stock_minimum'] ? 'text-red-600' : '' ?>"><?= angka($stok) ?> <?= e($barang['satuan']) ?></b></p>

        <form method="post" action="<?= url('barang', ['action' => 'adjust', 'id' => $barang['id']]) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <div>
                <label class="label">Jenis Penyesuaian</label>
                <select name="jenis" class="input">
                    <option value="masuk" <?= $jenis === 'masuk' ? 'selected' : '' ?>>Stok Masuk (Bertambah)</option>
                    <option value="keluar" <?= $jenis === 'keluar' ? 'selected' : '' ?>>Stok Keluar (Berkurang)</option>
                </select>
            </div>
            <div>
                <label class="label">Jumlah *</label>
                <input type="number" name="qty" class="input" min="0.01" step="0.01" value="<?= e($qty) ?>" required>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Keterangan *</label>
                <textarea name="keterangan" class="input" rows="2" placeholder="Contoh: barang rusak, stok opname, kehilangan" required><?= e($keterangan) ?></textarea>
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2">
                <a href="<?= url('barang', ['action' => 'show', 'id' => $barang['id']]) ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Penyesuaian</button>
            </div>
        </form>
    </div>
</div>
