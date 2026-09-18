<?php $canCreate = has_role('Administrator') || has_role('Bendahara') || has_role('Petugas'); ?>
<?php
$produkData = [];
foreach (($produk ?? []) as $p) {
    $produkData[] = ['id' => (int)$p['id'], 'nama' => $p['name'], 'kode' => $p['kode'], 'barcode' => $p['barcode'] ?? '', 'harga' => (float)$p['harga_jual'], 'stok' => (float)$p['stock'], 'satuan' => $p['satuan']];
}
$today = date('Y-m-d');
?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('penjualan') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="penjualan">
            <input type="date" name="dari" value="<?= e($dari) ?>" class="input w-full md:w-40">
            <input type="date" name="sampai" value="<?= e($sampai) ?>" class="input w-full md:w-40">
            <div class="relative flex-1">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nomor / pelanggan...">
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
            <button type="button" onclick="openPenjualanModal()" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Penjualan Baru</button>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Pelanggan</th><th class="text-right">Total</th><th>Metode</th><th>Status</th><?php if (!empty($isHistory)): ?><th>Alasan Batal</th><?php endif; ?><th>User</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="<?= !empty($isHistory) ? 9 : 8 ?>" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
            <?php else: foreach ($pg['items'] as $t): ?>
                <?php
                    $txData = ['id'=>(int)$t['id'],'no_transaksi'=>$t['no_transaksi'],'tanggal'=>$t['tanggal'],'customer_id'=>$t['customer_id'],'payment_method'=>$t['payment_method'],'keterangan'=>$t['keterangan'] ?? '','status'=>$t['status']];
                    $detData = $detailsMap[$t['id']] ?? [];
                ?>
                <tr class="<?= $t['status'] === 'DIBATALKAN' ? 'opacity-50' : '' ?>">
                    <td class="font-mono text-xs"><?= e($t['no_transaksi']) ?></td>
                    <td class="whitespace-nowrap"><?= tanggal($t['tanggal']) ?></td>
                    <td><?= e($t['pelanggan'] ?? '-') ?></td>
                    <td class="text-right font-semibold whitespace-nowrap"><?= rupiah($t['total']) ?></td>
                    <td><span class="text-[11px] px-2 py-0.5 rounded-full <?= $t['payment_method'] === 'kredit' ? 'bg-fuchsia-100 text-fuchsia-700' : 'bg-emerald-100 text-emerald-700' ?> font-medium"><?= e($t['payment_method']) ?></span></td>
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
                            <a href="<?= url('penjualan', ['action' => 'show', 'id' => $t['id']]) ?>" class="btn btn-ghost p-1.5" title="Detail"><?= icon('eye', 'w-4 h-4') ?></a>
                            <a href="<?= url('penjualan', ['action' => 'struk', 'id' => $t['id']]) ?>" target="_blank" class="btn btn-ghost p-1.5" title="Cetak Struk"><?= icon('printer', 'w-4 h-4') ?></a>
                            <?php if (has_role('Administrator') || has_role('Bendahara')): ?>
                                <button type="button" onclick='openPenjualanEditModal(<?= json_encode($txData) ?>, <?= json_encode($detData) ?>)' class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></button>
                            <?php endif; ?>
                            <?php if (has_role('Administrator') && $t['status'] === 'AKTIF'): ?>
                                <form method="post" action="<?= url('penjualan', ['action' => 'destroy', 'id' => $t['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" onclick="return appConfirmSubmit(event, 'Hapus penjualan ini? Data tidak bisa dikembalikan.', 'Hapus')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
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

<div id="penjualanModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closePenjualanModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
            <h2 class="font-semibold text-slate-800">Faktur Penjualan Baru</h2>
            <button type="button" onclick="closePenjualanModal()" class="btn btn-ghost p-2 text-xl leading-none">&times;</button>
        </div>
        <form method="post" action="<?= url('penjualan', ['action' => 'store']) ?>" id="formPenjualanModal" class="p-6 space-y-6">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="label">Tanggal *</label>
                    <input type="date" name="tanggal" id="mTanggal" class="input" value="<?= e($today) ?>" required max="9999-12-31">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Pelanggan</label>
                    <select name="customer_id" class="input" id="mCustomer">
                        <option value="">- Umum / Tunai -</option>
                        <?php foreach (($pelanggan ?? []) as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label">Metode Pembayaran *</label>
                    <select name="metode" class="input" id="mMetode">
                        <option value="tunai">Tunai</option>
                        <option value="kredit">Kredit</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-slate-50 border border-emerald-200 rounded-xl px-4 py-3">
                <?= icon('printer', 'w-5 h-5 text-emerald-600') ?>
                <input type="text" id="mScan" class="input flex-1" placeholder="Scan barcode barang untuk tambah cepat..." autocomplete="off">
                <span class="text-xs text-slate-400 hidden sm:inline">Tekan Enter / gunakan scanner</span>
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
                <div id="mItemRows" class="divide-y divide-slate-100"></div>
            </div>
            <div class="flex justify-end">
                <button type="button" id="mBtnTambah" class="btn btn-secondary"><?= icon('plus', 'w-4 h-4') ?> Tambah Barang</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="sm:col-span-2">
                    <label class="label">Keterangan</label>
                    <input type="text" name="keterangan" id="mKeterangan" class="input" placeholder="Opsional...">
                </div>
                <div class="bg-slate-50 rounded-xl p-4 text-right">
                    <div class="text-sm text-slate-500">Total</div>
                    <div id="mTotalLabel" class="text-2xl font-bold text-emerald-600">Rp 0</div>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closePenjualanModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Penjualan</button>
            </div>
        </form>
    </div>
</div>

<div id="penjualanEditModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closePenjualanEditModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
            <div>
                <h2 class="font-semibold text-slate-800">Ubah Penjualan</h2>
                <p id="eSubtitle" class="text-sm text-slate-500"></p>
            </div>
            <button type="button" onclick="closePenjualanEditModal()" class="btn btn-ghost p-2 text-xl leading-none">&times;</button>
        </div>
        <div id="eBatalWarning" class="hidden mx-6 mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-red-700 text-sm">Transaksi dibatalkan — tidak dapat diubah.</div>
        <form method="post" action="" id="formPenjualanEditModal" class="p-6 space-y-6">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="label">Tanggal *</label>
                    <input type="date" name="tanggal" id="eTanggal" class="input" required max="9999-12-31">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Pelanggan</label>
                    <select name="customer_id" class="input" id="eCustomer">
                        <option value="">- Umum / Tunai -</option>
                        <?php foreach (($pelanggan ?? []) as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label">Metode Pembayaran *</label>
                    <select name="metode" class="input" id="eMetode">
                        <option value="tunai">Tunai</option>
                        <option value="kredit">Kredit</option>
                    </select>
                </div>
            </div>
            <div id="eScanWrap" class="flex items-center gap-2 bg-slate-50 border border-emerald-200 rounded-xl px-4 py-3">
                <?= icon('printer', 'w-5 h-5 text-emerald-600') ?>
                <input type="text" id="eScan" class="input flex-1" placeholder="Scan barcode barang untuk tambah cepat..." autocomplete="off">
                <span class="text-xs text-slate-400 hidden sm:inline">Tekan Enter / gunakan scanner</span>
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
                <div id="eItemRows" class="divide-y divide-slate-100"></div>
            </div>
            <div class="flex justify-end">
                <button type="button" id="eBtnTambah" class="btn btn-secondary"><?= icon('plus', 'w-4 h-4') ?> Tambah Barang</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="sm:col-span-2">
                    <label class="label">Keterangan</label>
                    <input type="text" name="keterangan" id="eKeterangan" class="input" placeholder="Opsional...">
                </div>
                <div class="bg-slate-50 rounded-xl p-4 text-right">
                    <div class="text-sm text-slate-500">Total</div>
                    <div id="eTotalLabel" class="text-2xl font-bold text-emerald-600">Rp 0</div>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closePenjualanEditModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" id="eSubmitBtn" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Perubahan</button>
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
    .select2-container { z-index: 60; }
</style>
<script src="<?= asset('assets/vendor/jquery/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= asset('assets/vendor/select2/js/select2.min.js') ?>"></script>
<script>
var PRODUK = <?= json_encode($produkData) ?>;
var _penjualanStoreUrl = "<?= url('penjualan', ['action' => 'store']) ?>";
var _penjualanUpdateBase = "<?= url('penjualan', ['action' => 'update']) ?>";

function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); }
function angka(v) { return Number(String(v).replace(/\./g,'').replace(/,/g,'.').replace(/[^0-9.\-]/g,'')) || 0; }
function formatRibuan(el){ var d = el.value.replace(/[^0-9]/g,''); el.value = d ? Number(d).toLocaleString('id-ID') : ''; }

function openPenjualanModal(){
    var m = document.getElementById('penjualanModal');
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow='hidden';
    if(!document.querySelectorAll('#mItemRows .row-item').length) addRowM({});
    setTimeout(function(){ var s=document.getElementById('mScan'); if(s) s.focus(); },100);
}
function closePenjualanModal(){
    var m=document.getElementById('penjualanModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow='';
}
function openPenjualanEditModal(tx, details){
    var isBatal = tx.status === 'DIBATALKAN';
    document.getElementById('eSubtitle').textContent = (tx.no_transaksi||'') + ' — ' + (tx.tanggal||'');
    document.getElementById('eTanggal').value = tx.tanggal || '';
    document.getElementById('eTanggal').disabled = isBatal;
    document.getElementById('eCustomer').value = tx.customer_id || '';
    document.getElementById('eCustomer').disabled = isBatal;
    document.getElementById('eMetode').value = tx.payment_method || 'tunai';
    document.getElementById('eMetode').disabled = isBatal;
    document.getElementById('eKeterangan').value = tx.keterangan || '';
    document.getElementById('eKeterangan').disabled = isBatal;
    document.getElementById('eBatalWarning').classList.toggle('hidden', !isBatal);
    document.getElementById('eSubmitBtn').classList.toggle('hidden', isBatal);
    document.getElementById('eBtnTambah').classList.toggle('hidden', isBatal);
    document.getElementById('eScan').disabled = isBatal;
    document.getElementById('eScanWrap').classList.toggle('opacity-50', isBatal);
    document.getElementById('eScanWrap').classList.toggle('pointer-events-none', isBatal);
    document.getElementById('formPenjualanEditModal').action = _penjualanUpdateBase + '&id=' + encodeURIComponent(tx.id);
    var wrap = document.getElementById('eItemRows');
    wrap.querySelectorAll('.row-item').forEach(function(r){
        var sel=r.querySelector('.i-produk'); if(sel && $(sel).data('select2')) $(sel).select2('destroy');
        r.remove();
    });
    (details||[]).forEach(function(r){ addRowE(r, isBatal); });
    if(!(details||[]).length && !isBatal) addRowE({}, false);
    else hitungTotalE();
    var em=document.getElementById('penjualanEditModal');
    em.classList.remove('hidden'); em.classList.add('flex');
    document.body.style.overflow='hidden';
}
function closePenjualanEditModal(){
    var em=document.getElementById('penjualanEditModal');
    em.classList.add('hidden'); em.classList.remove('flex');
    document.body.style.overflow='';
}
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ closePenjualanModal(); closePenjualanEditModal(); }
});

function hitungTotalM(){
    var total=0;
    document.querySelectorAll('#mItemRows .row-item').forEach(function(row){
        var qty=Math.floor(angka(row.querySelector('.i-qty').value));
        var harga=Math.floor(angka(row.querySelector('.i-harga').value));
        var diskon=Math.floor(angka(row.querySelector('.i-diskon').value));
        var sub=Math.max(0, qty*harga - diskon);
        total+=sub;
        row.querySelector('.i-sub').textContent=rupiah(sub);
    });
    document.getElementById('mTotalLabel').textContent=rupiah(total);
}
function hitungTotalE(){
    var total=0;
    document.querySelectorAll('#eItemRows .row-item').forEach(function(row){
        var qty=Math.floor(angka(row.querySelector('.i-qty').value));
        var harga=Math.floor(angka(row.querySelector('.i-harga').value));
        var diskon=Math.floor(angka(row.querySelector('.i-diskon').value));
        var sub=Math.max(0, qty*harga - diskon);
        total+=sub;
        row.querySelector('.i-sub').textContent=rupiah(sub);
    });
    document.getElementById('eTotalLabel').textContent=rupiah(total);
}
function addRowM(pre){
    pre=Object.assign({product_id:'',qty:'',harga:'',diskon:''},pre||{});
    var row=document.createElement('div');
    row.className='row-item grid grid-cols-12 gap-2 px-4 py-2 items-center';
    var opts='<option value="">- Pilih -</option>';
    PRODUK.forEach(function(p){
        var sel=String(p.id)===String(pre.product_id)?' selected':'';
        opts+='<option value="'+p.id+'" data-harga="'+p.harga+'" data-stok="'+p.stok+'" data-nama="'+p.nama+'"'+sel+'>'+p.kode+' - '+p.nama+' (stok: '+p.stok+')</option>';
    });
    var hargaStr = pre.harga!=='' && pre.harga!=null && String(pre.harga).trim()!=='' ? Number(String(pre.harga).replace(/[^\d]/g,'')||0).toLocaleString('id-ID') : '';
    var diskonStr = pre.diskon!=='' && pre.diskon!=null && String(pre.diskon).trim()!=='' && Number(String(pre.diskon).replace(/[^\d]/g,'')||0) ? Number(String(pre.diskon).replace(/[^\d]/g,'')||0).toLocaleString('id-ID') : '';
    var qtyStr = pre.qty!=='' && pre.qty!=null && String(pre.qty).trim()!=='' ? String(parseInt(String(pre.qty).replace(/[^\d]/g,'')||0,10)) : '';
    row.innerHTML='<div class="col-span-4"><select name="product_id[]" class="input i-produk text-sm" data-no-cs="1" data-placeholder="- Pilih barang -">'+opts+'</select></div>'+
        '<div class="col-span-1"><input type="text" inputmode="numeric" name="qty[]" class="input i-qty text-sm text-center" value="'+qtyStr+'" placeholder="1"></div>'+
        '<div class="col-span-2"><input type="text" name="harga[]" class="input i-harga text-sm text-right" inputmode="numeric" value="'+hargaStr+'" placeholder="Harga"></div>'+
        '<div class="col-span-2"><input type="text" name="diskon[]" class="input i-diskon text-sm text-right" inputmode="numeric" value="'+diskonStr+'" placeholder="0"></div>'+
        '<div class="col-span-2 i-sub text-sm font-semibold text-right">Rp 0</div>'+
        '<div class="col-span-1 text-right"><button type="button" class="btn btn-ghost p-1.5 btn-hapus">&times;</button></div>';
    var selEl=row.querySelector('.i-produk');
    $(selEl).on('change', function(){
        var opt=this.options[this.selectedIndex];
        var h=opt.dataset.harga ? Math.floor(Number(opt.dataset.harga)) : '';
        row.querySelector('.i-harga').value = h!=='' ? Number(h).toLocaleString('id-ID') : '';
        if(!row.querySelector('.i-qty').value) row.querySelector('.i-qty').value='1';
        hitungTotalM();
    });
    $(selEl).select2({width:'100%',placeholder:$(selEl).data('placeholder'),allowClear:true,dropdownParent:$('#penjualanModal')});
    row.querySelector('.i-qty').addEventListener('input', function(){ this.value=this.value.replace(/[^0-9]/g,''); hitungTotalM(); });
    row.querySelector('.i-harga').addEventListener('input', function(){ formatRibuan(this); hitungTotalM(); });
    row.querySelector('.i-diskon').addEventListener('input', function(){ formatRibuan(this); hitungTotalM(); });
    row.querySelector('.btn-hapus').addEventListener('click', function(){ $(selEl).select2('destroy'); row.remove(); hitungTotalM(); });
    document.getElementById('mItemRows').appendChild(row);
    hitungTotalM();
}
function addRowE(pre, isBatal){
    pre=Object.assign({product_id:'',qty:'',harga:'',diskon:''},pre||{});
    var row=document.createElement('div');
    row.className='row-item grid grid-cols-12 gap-2 px-4 py-2 items-center';
    var opts='<option value="">- Pilih -</option>';
    PRODUK.forEach(function(p){
        var sel=String(p.id)===String(pre.product_id)?' selected':'';
        opts+='<option value="'+p.id+'" data-harga="'+p.harga+'" data-stok="'+p.stok+'" data-nama="'+p.nama+'"'+sel+'>'+p.kode+' - '+p.nama+' (stok: '+p.stok+')</option>';
    });
    var hargaStr = pre.harga!=='' && pre.harga!=null && String(pre.harga).trim()!=='' ? Number(String(pre.harga).replace(/[^\d]/g,'')||0).toLocaleString('id-ID') : '';
    var diskonStr = pre.diskon!=='' && pre.diskon!=null && String(pre.diskon).trim()!=='' && Number(String(pre.diskon).replace(/[^\d]/g,'')||0) ? Number(String(pre.diskon).replace(/[^\d]/g,'')||0).toLocaleString('id-ID') : '';
    var qtyStr = pre.qty!=='' && pre.qty!=null && String(pre.qty).trim()!=='' ? String(parseInt(String(pre.qty).replace(/[^\d]/g,'')||0,10)) : '';
    var dis = isBatal ? ' disabled' : '';
    var btnHtml = isBatal ? '' : '<button type="button" class="btn btn-ghost p-1.5 btn-hapus">&times;</button>';
    row.innerHTML='<div class="col-span-4"><select name="product_id[]" class="input i-produk text-sm" data-no-cs="1" data-placeholder="- Pilih barang -"'+dis+'>'+opts+'</select></div>'+
        '<div class="col-span-1"><input type="text" inputmode="numeric" name="qty[]" class="input i-qty text-sm text-center" value="'+qtyStr+'" placeholder="1"'+dis+'></div>'+
        '<div class="col-span-2"><input type="text" name="harga[]" class="input i-harga text-sm text-right" inputmode="numeric" value="'+hargaStr+'" placeholder="Harga"'+dis+'></div>'+
        '<div class="col-span-2"><input type="text" name="diskon[]" class="input i-diskon text-sm text-right" inputmode="numeric" value="'+diskonStr+'" placeholder="0"'+dis+'></div>'+
        '<div class="col-span-2 i-sub text-sm font-semibold text-right">Rp 0</div>'+
        '<div class="col-span-1 text-right">'+btnHtml+'</div>';
    var selEl=row.querySelector('.i-produk');
    if(!isBatal){
        $(selEl).on('change', function(){
            var opt=this.options[this.selectedIndex];
            var h=opt.dataset.harga ? Math.floor(Number(opt.dataset.harga)) : '';
            row.querySelector('.i-harga').value = h!=='' ? Number(h).toLocaleString('id-ID') : '';
            if(!row.querySelector('.i-qty').value) row.querySelector('.i-qty').value='1';
            hitungTotalE();
        });
        $(selEl).select2({width:'100%',placeholder:$(selEl).data('placeholder'),allowClear:true,dropdownParent:$('#penjualanEditModal')});
        row.querySelector('.i-qty').addEventListener('input', function(){ this.value=this.value.replace(/[^0-9]/g,''); hitungTotalE(); });
        row.querySelector('.i-harga').addEventListener('input', function(){ formatRibuan(this); hitungTotalE(); });
        row.querySelector('.i-diskon').addEventListener('input', function(){ formatRibuan(this); hitungTotalE(); });
        var bh=row.querySelector('.btn-hapus');
        if(bh) bh.addEventListener('click', function(){ $(selEl).select2('destroy'); row.remove(); hitungTotalE(); });
    }
    document.getElementById('eItemRows').appendChild(row);
    hitungTotalE();
}

(function(){
    var btnM=document.getElementById('mBtnTambah');
    if(btnM) btnM.addEventListener('click', function(){ addRowM({}); });
    var btnE=document.getElementById('eBtnTambah');
    if(btnE) btnE.addEventListener('click', function(){ addRowE({}, false); });
    var selM=document.getElementById('mMetode');
    if(selM) selM.addEventListener('change', function(){
        var kredit=this.value==='kredit';
        document.getElementById('mCustomer').required=kredit;
        if(kredit) document.getElementById('mCustomer').focus();
    });
    var selE=document.getElementById('eMetode');
    if(selE) selE.addEventListener('change', function(){
        var kredit=this.value==='kredit';
        document.getElementById('eCustomer').required=kredit;
    });
    var scanM=document.getElementById('mScan');
    if(scanM){
        scanM.addEventListener('keydown', function(e){ if(e.key==='Enter') e.preventDefault(); });
        scanM.addEventListener('change', function(){
            var bc=this.value.trim(); if(bc==='') return;
            var p=null; for(var i=0;i<PRODUK.length;i++){ if(String(PRODUK[i].barcode)===bc){ p=PRODUK[i]; break; } }
            if(!p){ Swal.fire({icon:'warning',title:'Barcode tidak ditemukan',text:'Barang dengan barcode "'+bc+'" belum terdaftar.',confirmButtonText:'OK'}); }
            else {
                var existing=null;
                document.querySelectorAll('#mItemRows .row-item').forEach(function(row){ if(row.querySelector('.i-produk').value===String(p.id)) existing=row; });
                if(existing){ var qtyNow=Math.floor(angka(existing.querySelector('.i-qty').value))||1; existing.querySelector('.i-qty').value=qtyNow+1; hitungTotalM(); }
                else addRowM({product_id:p.id,qty:1,harga:p.harga,diskon:''});
            }
            this.value=''; this.focus();
        });
    }
    var scanE=document.getElementById('eScan');
    if(scanE){
        scanE.addEventListener('keydown', function(e){ if(e.key==='Enter') e.preventDefault(); });
        scanE.addEventListener('change', function(){
            var bc=this.value.trim(); if(bc==='') return;
            var p=null; for(var i=0;i<PRODUK.length;i++){ if(String(PRODUK[i].barcode)===bc){ p=PRODUK[i]; break; } }
            if(!p){ Swal.fire({icon:'warning',title:'Barcode tidak ditemukan',text:'Barang dengan barcode "'+bc+'" belum terdaftar.',confirmButtonText:'OK'}); }
            else {
                var existing=null;
                document.querySelectorAll('#eItemRows .row-item').forEach(function(row){ if(row.querySelector('.i-produk').value===String(p.id)) existing=row; });
                if(existing){ var qtyNow=Math.floor(angka(existing.querySelector('.i-qty').value))||1; existing.querySelector('.i-qty').value=qtyNow+1; hitungTotalE(); }
                else addRowE({product_id:p.id,qty:1,harga:p.harga,diskon:''}, false);
            }
            this.value=''; this.focus();
        });
    }
})();
</script>
