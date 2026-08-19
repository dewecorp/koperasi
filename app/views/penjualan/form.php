<?php
$produkData = [];
foreach ($produk as $p) {
    $produkData[] = ['id' => (int)$p['id'], 'nama' => $p['name'], 'kode' => $p['kode'], 'barcode' => $p['barcode'] ?? '', 'harga' => (float)$p['harga_jual'], 'stok' => (float)$p['stock'], 'satuan' => $p['satuan']];
}
$tanggalForm = input('tanggal', date('Y-m-d'));
?>
<div class="max-w-5xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-semibold text-slate-800">Faktur Penjualan Baru</h2>
            <a href="<?= url('penjualan') ?>" class="btn btn-ghost"><?= icon('chevron-left', 'w-4 h-4') ?> Kembali</a>
        </div>

        <form method="post" action="<?= url('penjualan', ['action' => 'store']) ?>" id="formPenjualan" class="space-y-6">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="label">Tanggal *</label>
                    <input type="date" name="tanggal" class="input" value="<?= e($tanggalForm) ?>" required max="9999-12-31">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Pelanggan</label>
                    <select name="customer_id" class="input" id="selectCustomer">
                        <option value="">- Umum / Tunai -</option>
                        <?php foreach ($pelanggan as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
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

            <div class="flex items-center gap-2 bg-slate-50 border border-emerald-200 rounded-xl px-4 py-3">
                <?= icon('printer', 'w-5 h-5 text-emerald-600') ?>
                <input type="text" id="inputScan" class="input flex-1" placeholder="Scan barcode barang untuk tambah cepat..." autocomplete="off">
                <span class="text-xs text-slate-400">Tekan Enter / gunakan scanner</span>
            </div>

            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-slate-50 px-4 py-2 grid grid-cols-12 gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    <div class="col-span-4">Barang</div>
                    <div class="col-span-1">Jumlah</div>
                    <div class="col-span-2">Harga Jual</div>
                    <div class="col-span-2">Diskon</div>
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
                    <div id="totalLabel" class="text-2xl font-bold text-emerald-600">Rp 0</div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="<?= url('penjualan') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Penjualan</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var PRODUK = <?= json_encode($produkData) ?>;
    var counter = 0;

    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); }

    function angka(v) { return Number(String(v).replace(/[^\d.-]/g, '')) || 0; }

    function hitungTotal() {
        var total = 0;
        document.querySelectorAll('#itemRows .row-item').forEach(function (row) {
            var qty = angka(row.querySelector('.i-qty').value);
            var harga = angka(row.querySelector('.i-harga').value);
            var diskon = angka(row.querySelector('.i-diskon').value);
            var sub = Math.max(0, qty * harga - diskon);
            total += sub;
            row.querySelector('.i-sub').textContent = rupiah(sub);
        });
        document.getElementById('totalLabel').textContent = rupiah(total);
    }

    function addRow(pre) {
        pre = pre || { product_id: '', qty: '', harga: '', diskon: '' };
        var row = document.createElement('div');
        row.className = 'row-item grid grid-cols-12 gap-2 px-4 py-2 items-center';
        var opts = '<option value="">- Pilih -</option>';
        PRODUK.forEach(function (p) {
            var sel = String(p.id) === String(pre.product_id) ? ' selected' : '';
            opts += '<option value="' + p.id + '" data-harga="' + p.harga + '" data-stok="' + p.stok + '" data-nama="' + p.nama + '"' + sel + '>' + p.kode + ' - ' + p.nama + ' (stok: ' + p.stok + ')</option>';
        });
        row.innerHTML =
            '<div class="col-span-4"><select name="product_id[]" class="input i-produk text-sm">' + opts + '</select></div>' +
            '<div class="col-span-1"><input type="number" name="qty[]" class="input i-qty text-sm" min="0.01" step="0.01" value="' + (pre.qty || '') + '"></div>' +
            '<div class="col-span-2"><input type="text" name="harga[]" class="input i-harga text-sm" inputmode="numeric" value="' + (pre.harga || '') + '"></div>' +
            '<div class="col-span-2"><input type="text" name="diskon[]" class="input i-diskon text-sm" inputmode="numeric" value="' + (pre.diskon || '') + '"></div>' +
            '<div class="col-span-2 i-sub text-sm font-semibold text-right">Rp 0</div>' +
            '<div class="col-span-1 text-right"><button type="button" class="btn btn-ghost p-1.5 btn-hapus">' + '&times;' + '</button></div>';

        row.querySelector('.i-produk').addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            row.querySelector('.i-harga').value = opt.dataset.harga || '';
            row.querySelector('.i-qty').value = 1;
            hitungTotal();
        });
        row.querySelectorAll('.i-qty,.i-harga,.i-diskon').forEach(function (el) {
            el.addEventListener('input', hitungTotal);
        });
        row.querySelector('.btn-hapus').addEventListener('click', function () {
            row.remove();
            hitungTotal();
        });

        document.getElementById('itemRows').appendChild(row);
        counter++;
        hitungTotal();
    }

    document.getElementById('btnTambahItem').addEventListener('click', function () {
        addRow({});
    });
    document.getElementById('selectMetode').addEventListener('change', function () {
        var kredit = this.value === 'kredit';
        document.getElementById('selectCustomer').required = kredit;
        if (kredit) { document.getElementById('selectCustomer').focus(); }
    });

    // ===== Scanner barcode: tambah barang ke keranjang =====
    var inputScan = document.getElementById('inputScan');
    if (inputScan) {
        inputScan.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') e.preventDefault();
        });
        inputScan.addEventListener('change', function () {
            var bc = this.value.trim();
            if (bc === '') return;
            var p = null;
            for (var i = 0; i < PRODUK.length; i++) {
                if (String(PRODUK[i].barcode) === bc) { p = PRODUK[i]; break; }
            }
            if (!p) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Barcode tidak ditemukan',
                    text: 'Barang dengan barcode "' + bc + '" belum terdaftar. Tambahkan lewat menu Data Barang.',
                    confirmButtonText: 'OK'
                });
            } else {
                var existing = null;
                document.querySelectorAll('#itemRows .row-item').forEach(function (row) {
                    if (row.querySelector('.i-produk').value === String(p.id)) existing = row;
                });
                if (existing) {
                    var qtyNow = angka(existing.querySelector('.i-qty').value) || 1;
                    existing.querySelector('.i-qty').value = qtyNow + 1;
                    hitungTotal();
                } else {
                    addRow({ product_id: p.id, qty: 1, harga: p.harga, diskon: '' });
                }
            }
            this.value = '';
            this.focus();
        });
    }

    // baris pertama
    addRow({});
})();
</script>