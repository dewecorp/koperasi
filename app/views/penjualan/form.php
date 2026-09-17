<?php
$produkData = [];
foreach ($produk as $p) {
    $produkData[] = ['id' => (int)$p['id'], 'nama' => $p['name'], 'kode' => $p['kode'], 'barcode' => $p['barcode'] ?? '', 'harga' => (float)$p['harga_jual'], 'stok' => (float)$p['stock'], 'satuan' => $p['satuan']];
}
$tanggalForm = input('tanggal', date('Y-m-d'));
?>
<div class="w-full">
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
                    <div class="col-span-1 text-center">Qty</div>
                    <div class="col-span-2 text-right">Harga Jual</div>
                    <div class="col-span-2 text-right">Diskon</div>
                    <div class="col-span-2 text-right">Subtotal</div>
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

<link rel="stylesheet" href="<?= asset('assets/vendor/select2/css/select2.min.css') ?>">
<style>
    .select2-container--default .select2-selection--single { background-color: #fff; border: 1px solid #cbd5e1; border-radius: 0.5rem; height: 39px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { color: #1e293b; font-size: 0.875rem; line-height: 37px; padding-left: 12px; padding-right: 26px; }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color: #94a3b8; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 37px; right: 6px; }
    .select2-container--default .select2-selection--single .select2-selection__clear { color: #94a3b8; }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single { border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.15); }
    .select2-dropdown { border-color: #cbd5e1; border-radius: 0.5rem; overflow: hidden; font-size: 0.875rem; }
    .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid #cbd5e1; border-radius: 0.375rem; }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.15); }
    .select2-container--default .select2-results__option[aria-selected] { padding: 0.375rem 0.75rem; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #059669; color: #fff; }
</style>
<script src="<?= asset('assets/vendor/jquery/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= asset('assets/vendor/select2/js/select2.min.js') ?>"></script>
<script>
(function () {
    var PRODUK = <?= json_encode($produkData) ?>;

    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); }
    function angka(v) { return Number(String(v).replace(/\./g,'').replace(/,/g,'.').replace(/[^0-9.\-]/g,'')) || 0; }
    function formatRibuan(el){
        var d = el.value.replace(/[^0-9]/g,'');
        el.value = d ? Number(d).toLocaleString('id-ID') : '';
    }

    function hitungTotal() {
        var total = 0;
        document.querySelectorAll('#itemRows .row-item').forEach(function (row) {
            var qty = Math.floor(angka(row.querySelector('.i-qty').value));
            var harga = Math.floor(angka(row.querySelector('.i-harga').value));
            var diskon = Math.floor(angka(row.querySelector('.i-diskon').value));
            var sub = Math.max(0, qty * harga - diskon);
            total += sub;
            row.querySelector('.i-sub').textContent = rupiah(sub);
        });
        document.getElementById('totalLabel').textContent = rupiah(total);
    }

    function addRow(pre) {
        pre = Object.assign({ product_id: '', qty: '', harga: '', diskon: '' }, pre || {});
        var row = document.createElement('div');
        row.className = 'row-item grid grid-cols-12 gap-2 px-4 py-2 items-center';
        var opts = '<option value="">- Pilih -</option>';
        PRODUK.forEach(function (p) {
            var sel = String(p.id) === String(pre.product_id) ? ' selected' : '';
            opts += '<option value="' + p.id + '" data-harga="' + p.harga + '" data-stok="' + p.stok + '" data-nama="' + p.nama + '"' + sel + '>' + p.kode + ' - ' + p.nama + ' (stok: ' + p.stok + ')</option>';
        });
        var hargaStr = pre.harga !== '' && pre.harga != null && String(pre.harga).trim() !== '' ? Number(String(pre.harga).replace(/[^\d]/g,'')||0).toLocaleString('id-ID') : '';
        var diskonStr = pre.diskon !== '' && pre.diskon != null && String(pre.diskon).trim() !== '' && Number(String(pre.diskon).replace(/[^\d]/g,'')||0) ? Number(String(pre.diskon).replace(/[^\d]/g,'')||0).toLocaleString('id-ID') : '';
        var qtyStr = pre.qty !== '' && pre.qty != null && String(pre.qty).trim() !== '' ? String(parseInt(String(pre.qty).replace(/[^\d]/g,'')||0,10)) : '';
        row.innerHTML =
            '<div class="col-span-4"><select name="product_id[]" class="input i-produk text-sm" data-no-cs="1" data-placeholder="- Pilih barang -">' + opts + '</select></div>' +
            '<div class="col-span-1"><input type="text" inputmode="numeric" name="qty[]" class="input i-qty text-sm text-center" value="' + qtyStr + '" placeholder="1"></div>' +
            '<div class="col-span-2"><input type="text" name="harga[]" class="input i-harga text-sm text-right" inputmode="numeric" value="' + hargaStr + '" placeholder="Harga"></div>' +
            '<div class="col-span-2"><input type="text" name="diskon[]" class="input i-diskon text-sm text-right" inputmode="numeric" value="' + diskonStr + '" placeholder="0"></div>' +
            '<div class="col-span-2 i-sub text-sm font-semibold text-right">Rp 0</div>' +
            '<div class="col-span-1 text-right"><button type="button" class="btn btn-ghost p-1.5 btn-hapus">&times;</button></div>';

        var selEl = row.querySelector('.i-produk');
        $(selEl).on('change', function () {
            var opt = this.options[this.selectedIndex];
            var h = opt.dataset.harga ? Math.floor(Number(opt.dataset.harga)) : '';
            row.querySelector('.i-harga').value = h !== '' ? Number(h).toLocaleString('id-ID') : '';
            if (!row.querySelector('.i-qty').value) row.querySelector('.i-qty').value = '1';
            hitungTotal();
        });
        $(selEl).select2({ width: '100%', placeholder: $(selEl).data('placeholder'), allowClear: true });
        row.querySelector('.i-qty').addEventListener('input', function(){ this.value = this.value.replace(/[^0-9]/g,''); hitungTotal(); });
        row.querySelector('.i-harga').addEventListener('input', function(){ formatRibuan(this); hitungTotal(); });
        row.querySelector('.i-diskon').addEventListener('input', function(){ formatRibuan(this); hitungTotal(); });
        row.querySelector('.btn-hapus').addEventListener('click', function () {
            $(selEl).select2('destroy');
            row.remove();
            hitungTotal();
        });

        document.getElementById('itemRows').appendChild(row);
        hitungTotal();
    }

    document.getElementById('btnTambahItem').addEventListener('click', function () { addRow({}); });
    document.getElementById('selectMetode').addEventListener('change', function () {
        var kredit = this.value === 'kredit';
        document.getElementById('selectCustomer').required = kredit;
        if (kredit) { document.getElementById('selectCustomer').focus(); }
    });

    var inputScan = document.getElementById('inputScan');
    if (inputScan) {
        inputScan.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
        inputScan.addEventListener('change', function () {
            var bc = this.value.trim();
            if (bc === '') return;
            var p = null;
            for (var i = 0; i < PRODUK.length; i++) { if (String(PRODUK[i].barcode) === bc) { p = PRODUK[i]; break; } }
            if (!p) {
                Swal.fire({ icon: 'warning', title: 'Barcode tidak ditemukan', text: 'Barang dengan barcode "' + bc + '" belum terdaftar.', confirmButtonText: 'OK' });
            } else {
                var existing = null;
                document.querySelectorAll('#itemRows .row-item').forEach(function (row) { if (row.querySelector('.i-produk').value === String(p.id)) existing = row; });
                if (existing) { var qtyNow = Math.floor(angka(existing.querySelector('.i-qty').value)) || 1; existing.querySelector('.i-qty').value = qtyNow + 1; hitungTotal(); }
                else { addRow({ product_id: p.id, qty: 1, harga: p.harga, diskon: '' }); }
            }
            this.value = ''; this.focus();
        });
    }

    addRow({});
})();
</script>
