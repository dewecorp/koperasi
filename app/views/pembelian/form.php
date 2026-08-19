<?php
$produkData = [];
foreach ($produk as $p) {
    $produkData[] = ['id' => (int)$p['id'], 'nama' => $p['name'], 'kode' => $p['kode'], 'harga' => (float)$p['harga_beli'], 'satuan' => $p['satuan']];
}
?>
<div class="max-w-5xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-semibold text-slate-800">Faktur Pembelian Baru</h2>
            <a href="<?= url('pembelian') ?>" class="btn btn-ghost"><?= icon('chevron-left', 'w-4 h-4') ?> Kembali</a>
        </div>

        <form method="post" action="<?= url('pembelian', ['action' => 'store']) ?>" id="formPembelian" class="space-y-6">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="label">Tanggal *</label>
                    <input type="date" name="tanggal" class="input" value="<?= e(input('tanggal', date('Y-m-d'))) ?>" required max="9999-12-31">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Supplier</label>
                    <select name="supplier_id" class="input" id="selectSupplier">
                        <option value="">- Pilih -</option>
                        <?php foreach ($supplier as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label">Metode Pembayaran *</label>
                    <select name="metode" class="input" id="selectMetode">
                        <option value="tunai">Tunai</option>
                        <option value="kredit">Kredit</option>
                    </select>
                </div>
            </div>

            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-slate-50 px-4 py-2 grid grid-cols-12 gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    <div class="col-span-4">Barang</div>
                    <div class="col-span-2">Jumlah</div>
                    <div class="col-span-3">Harga Beli</div>
                    <div class="col-span-2">Subtotal</div>
                    <div class="col-span-1"></div>
                </div>
                <div id="itemRows" class="divide-y divide-slate-100"></div>
            </div>

            <div class="flex justify-end">
                <button type="button" id="btnTambahItem" class="btn btn-secondary"><?= icon('plus', 'w-4 h-4') ?> Tambah Barang</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="sm:col-span-2">
                    <label class="label">Keterangan</label>
                    <input type="text" name="keterangan" class="input" placeholder="Opsional..." value="<?= e(old('keterangan')) ?>">
                </div>
                <div class="bg-slate-50 rounded-xl p-4 text-right">
                    <div class="text-sm text-slate-500">Total</div>
                    <div id="totalLabel" class="text-2xl font-bold text-amber-600">Rp 0</div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="<?= url('pembelian') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Pembelian</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var PRODUK = <?= json_encode($produkData) ?>;

    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); }

    function hitungTotal() {
        var total = 0;
        document.querySelectorAll('#itemRows .row-item').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.i-qty').value) || 0;
            var harga = parseFloat(row.querySelector('.i-harga').value) || 0;
            var sub = qty * harga;
            total += sub;
            row.querySelector('.i-sub').textContent = rupiah(sub);
        });
        document.getElementById('totalLabel').textContent = rupiah(total);
    }

    function addRow(pre) {
        pre = pre || {};
        var row = document.createElement('div');
        row.className = 'row-item grid grid-cols-12 gap-2 px-4 py-2 items-center';
        var opts = '<option value="">- Pilih -</option>';
        PRODUK.forEach(function (p) {
            var sel = String(p.id) === String(pre.product_id) ? ' selected' : '';
            opts += '<option value="' + p.id + '" data-harga="' + p.harga + '"' + sel + '>' + p.kode + ' - ' + p.nama + '</option>';
        });
        row.innerHTML =
            '<div class="col-span-4"><select name="product_id[]" class="input i-produk text-sm">' + opts + '</select></div>' +
            '<div class="col-span-2"><input type="number" name="qty[]" class="input i-qty text-sm" min="0.01" step="0.01" value="' + (pre.qty || '') + '"></div>' +
            '<div class="col-span-3"><input type="number" name="harga[]" class="input i-harga text-sm" min="0" step="0.01" value="' + (pre.harga || '') + '"></div>' +
            '<div class="col-span-2 i-sub text-sm font-semibold text-right">Rp 0</div>' +
            '<div class="col-span-1 text-right"><button type="button" class="btn btn-ghost p-1.5 btn-hapus">&times;</button></div>';

        row.querySelector('.i-produk').addEventListener('change', function () {
            row.querySelector('.i-harga').value = this.options[this.selectedIndex].dataset.harga || '';
            row.querySelector('.i-qty').value = 1;
            hitungTotal();
        });
        row.querySelectorAll('.i-qty,.i-harga').forEach(function (el) {
            el.addEventListener('input', hitungTotal);
        });
        row.querySelector('.btn-hapus').addEventListener('click', function () {
            row.remove();
            hitungTotal();
        });
        document.getElementById('itemRows').appendChild(row);
        hitungTotal();
    }

    document.getElementById('btnTambahItem').addEventListener('click', function () { addRow({}); });
    document.getElementById('selectMetode').addEventListener('change', function () {
        var kredit = this.value === 'kredit';
        document.getElementById('selectSupplier').required = kredit;
    });
    addRow({});
})();
</script>