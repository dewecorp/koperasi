<?php
$canCreate = has_role('Administrator') || has_role('Bendahara');
$produkData = [];
if (!empty($produk)) {
    foreach ($produk as $p) {
        $produkData[] = ['id' => (int)$p['id'], 'nama' => $p['name'], 'kode' => $p['kode'], 'barcode' => $p['barcode'] ?? '', 'harga' => (float)$p['harga_beli'], 'satuan' => $p['satuan']];
    }
}
$pembelianStoreUrl = url('pembelian', ['action' => 'store']);
$pembelianUpdateTpl = url('pembelian', ['action' => 'update', 'id' => '__ID__']);
?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('pembelian') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="pembelian">
            <input type="date" name="dari" value="<?= e($dari) ?>" class="input w-full md:w-40">
            <input type="date" name="sampai" value="<?= e($sampai) ?>" class="input w-full md:w-40">
            <div class="relative flex-1">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nomor / supplier...">
            </div>
            <select name="status" class="input w-full md:w-36">
                <?php if (!empty($isHistory)): ?>
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                <?php endif; ?>
                <option value="AKTIF" <?= $status === 'AKTIF' ? 'selected' : '' ?>>Aktif</option>
                <option value="DIBATALKAN" <?= $status === 'DIBATALKAN' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
        </form>
        <?php if ($canCreate): ?>
            <button type="button" onclick="openPembelianModal()" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Pembelian Baru</button>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Supplier</th><th class="text-right">Total</th><th>Metode</th><th>Status</th><?php if (!empty($isHistory)): ?><th>Alasan Batal</th><?php endif; ?><th>User</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="<?= !empty($isHistory) ? 9 : 8 ?>" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
            <?php else: foreach ($pg['items'] as $t): ?>
                <?php $editJson = htmlspecialchars(json_encode(['id' => $t['id'], 'tanggal' => $t['tanggal'], 'keterangan' => $t['keterangan'] ?? '', 'supplier' => $t['supplier'] ?? '-', 'no_transaksi' => $t['no_transaksi'], 'status' => $t['status']], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                <tr class="<?= $t['status'] === 'DIBATALKAN' ? 'opacity-50' : '' ?>">
                    <td class="font-mono text-xs"><?= e($t['no_transaksi']) ?></td>
                    <td class="whitespace-nowrap"><?= tanggal($t['tanggal']) ?></td>
                    <td><?= e($t['supplier'] ?? '-') ?></td>
                    <td class="text-right font-semibold whitespace-nowrap"><?= rupiah($t['total']) ?></td>
                    <td><span class="text-[11px] px-2 py-0.5 rounded-full <?= $t['payment_method'] === 'kredit' ? 'bg-orange-100 text-orange-700' : 'bg-emerald-100 text-emerald-700' ?> font-medium"><?= e($t['payment_method']) ?></span></td>
                    <td>
                        <?php if ($t['status'] === 'AKTIF'): ?>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">Aktif</span>
                        <?php else: ?>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium" title="<?= e($t['alasan_batal']) ?>">Dibatalkan</span>
                        <?php endif; ?>
                    </td>
                    <?php if (!empty($isHistory)): ?>
                        <td class="text-xs text-slate-500 max-w-[160px] truncate"><?= e($t['alasan_batal'] ?? '-') ?></td>
                    <?php endif; ?>
                    <td class="text-xs text-slate-500"><?= e($t['username']) ?></td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= url('pembelian', ['action' => 'show', 'id' => $t['id']]) ?>" class="btn btn-ghost p-1.5" title="Detail"><?= icon('eye', 'w-4 h-4') ?></a>
                            <?php if (has_role('Administrator') || has_role('Bendahara')): ?>
                                <button type="button" onclick="openPembelianEditModal(JSON.parse(this.dataset.edit))" data-edit="<?= $editJson ?>" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></button>
                            <?php endif; ?>
                            <?php if (has_role('Administrator') && $t['status'] === 'AKTIF'): ?>
                                <form method="post" action="<?= url('pembelian', ['action' => 'destroy', 'id' => $t['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" onclick="return appConfirmSubmit(event, 'Hapus pembelian ini? Data tidak bisa dikembalikan.', 'Hapus')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
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

<div id="pembelianModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closePembelianModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-xl shrink-0">
            <h3 class="font-semibold text-slate-800">Faktur Pembelian Baru</h3>
            <button type="button" onclick="closePembelianModal()" class="btn btn-ghost p-2"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" action="<?= $pembelianStoreUrl ?>" id="formPembelian" class="space-y-6 p-6 overflow-y-auto flex-1" style="overscroll-behavior:contain">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="label">Tanggal *</label>
                    <input type="date" name="tanggal" id="pm_tanggal" class="input" value="<?= e(date('Y-m-d')) ?>" required max="9999-12-31">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Supplier</label>
                    <select name="supplier_id" class="input" id="selectSupplier">
                        <option value="">- Pilih -</option>
                        <?php foreach (($supplier ?? []) as $s): ?>
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
            <div class="flex items-center gap-2 bg-slate-50 border border-amber-200 rounded-xl px-4 py-3">
                <?= icon('printer', 'w-5 h-5 text-amber-600') ?>
                <input type="text" id="inputScan" class="input flex-1" placeholder="Scan barcode barang untuk tambah cepat..." autocomplete="off">
                <span class="text-xs text-slate-400">Tekan Enter / gunakan scanner</span>
            </div>
            <div class="border border-slate-200 rounded-xl overflow-visible">
                <div class="bg-slate-50 px-4 py-2 grid grid-cols-12 gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wide rounded-t-xl">
                    <div class="col-span-4">Barang</div>
                    <div class="col-span-2">Jumlah</div>
                    <div class="col-span-3">Harga Beli</div>
                    <div class="col-span-2">Subtotal</div>
                    <div class="col-span-1"></div>
                </div>
                <div id="itemRows" class="divide-y divide-slate-100 overflow-visible"></div>
            </div>
            <div class="flex justify-end">
                <button type="button" id="btnTambahItem" class="btn btn-secondary"><?= icon('plus', 'w-4 h-4') ?> Tambah Barang</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="sm:col-span-2">
                    <label class="label">Keterangan</label>
                    <input type="text" name="keterangan" id="pm_keterangan" class="input" placeholder="Opsional...">
                </div>
                <div class="bg-slate-50 rounded-xl p-4 text-right">
                    <div class="text-sm text-slate-500">Total</div>
                    <div id="totalLabel" class="text-2xl font-bold text-amber-600">Rp 0</div>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closePembelianModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Pembelian</button>
            </div>
        </form>
    </div>
</div>

<div id="pembelianEditModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closePembelianEditModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg">
        <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-800">Ubah Pembelian</h3>
                <p id="pembelianEditSubtitle" class="text-sm text-slate-500">Hanya tanggal & keterangan (item/stok/kas tidak diubah)</p>
            </div>
            <button type="button" onclick="closePembelianEditModal()" class="btn btn-ghost p-2"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" id="formPembelianEditModal" action="" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <div id="pembelianEditBatalWarning" class="hidden px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-red-700 text-sm">Transaksi ini sudah dibatalkan, tidak dapat diubah.</div>
            <div>
                <label class="label">No. Transaksi</label>
                <input type="text" id="edit_no_transaksi" class="input bg-slate-50" disabled>
            </div>
            <div>
                <label class="label">Tanggal *</label>
                <input type="date" name="tanggal" id="edit_tanggal" class="input" required max="9999-12-31">
            </div>
            <div>
                <label class="label">Supplier</label>
                <input type="text" id="edit_supplier" class="input bg-slate-50" disabled>
            </div>
            <div>
                <label class="label">Keterangan</label>
                <input type="text" name="keterangan" id="edit_keterangan" class="input">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closePembelianEditModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" id="btnSaveEdit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
var _pembelianStoreUrl = <?= json_encode($pembelianStoreUrl) ?>;
var _pembelianUpdateTpl = <?= json_encode($pembelianUpdateTpl) ?>;
(function () {
    var PRODUK = <?= json_encode($produkData) ?>;
    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); }
    function angka(v) { return Number(String(v).replace(/\./g,'').replace(/,/g,'.').replace(/[^0-9.-]/g,'')) || 0; }
    function hitungTotal() {
        var total = 0;
        document.querySelectorAll('#itemRows .row-item').forEach(function (row) {
            var qty = angka(row.querySelector('.i-qty').value);
            var harga = angka(row.querySelector('.i-harga').value);
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
            '<div class="col-span-2"><input type="number" name="qty[]" class="input i-qty text-sm" min="1" step="1" value="' + (pre.qty || '') + '"></div>' +
            '<div class="col-span-3"><input type="text" name="harga[]" class="input i-harga text-sm" inputmode="numeric" value="' + (pre.harga || '') + '"></div>' +
            '<div class="col-span-2 i-sub text-sm font-semibold text-right">Rp 0</div>' +
            '<div class="col-span-1 text-right"><button type="button" class="btn btn-ghost p-1.5 btn-hapus">&times;</button></div>';
        row.querySelector('.i-produk').addEventListener('change', function () {
            var h = this.options[this.selectedIndex].dataset.harga || '';
            row.querySelector('.i-harga').value = h ? Number(h).toLocaleString('id-ID') : '';
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
    window.openPembelianModal = function() {
        var modal = document.getElementById('pembelianModal');
        document.getElementById('pm_tanggal').valueAsDate = new Date();
        document.getElementById('pm_keterangan').value = '';
        document.getElementById('selectSupplier').value = '';
        document.getElementById('selectMetode').value = 'tunai';
        document.getElementById('selectSupplier').required = false;
        document.getElementById('itemRows').innerHTML = '';
        addRow({});
        hitungTotal();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        setTimeout(function(){ document.getElementById('inputScan').focus(); }, 50);
    };
    window.closePembelianModal = function() {
        var m = document.getElementById('pembelianModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = '';
    };
    window.openPembelianEditModal = function(data) {
        if (typeof data !== 'object') {
            var args = Array.prototype.slice.call(arguments);
            data = { id: args[0], tanggal: args[1], keterangan: args[2] };
        }
        var modal = document.getElementById('pembelianEditModal');
        var form = document.getElementById('formPembelianEditModal');
        form.action = _pembelianUpdateTpl.replace('__ID__', data.id);
        document.getElementById('edit_no_transaksi').value = data.no_transaksi || '';
        document.getElementById('edit_tanggal').value = data.tanggal || '';
        document.getElementById('edit_supplier').value = data.supplier || '-';
        document.getElementById('edit_keterangan').value = data.keterangan || '';
        document.getElementById('pembelianEditSubtitle').textContent = (data.no_transaksi || '') + ' — hanya tanggal & keterangan (item/stok/kas tidak diubah)';
        var isBatal = data.status === 'DIBATALKAN';
        document.getElementById('pembelianEditBatalWarning').classList.toggle('hidden', !isBatal);
        document.getElementById('edit_tanggal').disabled = isBatal;
        document.getElementById('edit_keterangan').disabled = isBatal;
        document.getElementById('btnSaveEdit').classList.toggle('hidden', isBatal);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    };
    window.closePembelianEditModal = function() {
        var m = document.getElementById('pembelianEditModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = '';
    };
    document.getElementById('btnTambahItem').addEventListener('click', function () { addRow({}); });
    document.getElementById('selectMetode').addEventListener('change', function () {
        var kredit = this.value === 'kredit';
        document.getElementById('selectSupplier').required = kredit;
    });
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
                    var qtyNow = parseFloat(existing.querySelector('.i-qty').value) || 0;
                    existing.querySelector('.i-qty').value = qtyNow + 1;
                    hitungTotal();
                } else {
                    addRow({ product_id: p.id, qty: 1, harga: p.harga });
                }
            }
            this.value = '';
            this.focus();
        });
    }
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape'){
            closePembelianModal();
            closePembelianEditModal();
        }
    });
})();
</script>
