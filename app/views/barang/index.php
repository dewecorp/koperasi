<?php
$canEdit = has_role('Administrator') || has_role('Bendahara');
$daftarSatuanModal = ['pcs','buah','butir','biji','lusin','kodi','rim','pak','pack','set','box','kardus','botol','sachet','kg','gram','ons','liter','ml','galon','meter','cm','lembar','pasang','unit'];
$storeCreateUrl = url('barang', ['action' => 'store']);
$storeEditTpl = url('barang', ['action' => 'store', 'id' => '__ID__']);
$adjustTpl = url('barang', ['action' => 'adjust', 'id' => '__ID__']);
$cariBarcodeUrl = url('barang', ['action' => 'cariBarcode']);
?>
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
            <button type="button" onclick="openBarangModal(null)" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Tambah Barang</button>
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
                <?php $barangJson = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
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
                                <button type="button" onclick="openBarangModal(JSON.parse(this.dataset.barang))" data-barang="<?= $barangJson ?>" title="Ubah" class="btn btn-ghost p-1.5"><?= icon('edit', 'w-4 h-4') ?></button>
                                <button type="button" onclick="openAdjustModal(JSON.parse(this.dataset.barang))" data-barang="<?= $barangJson ?>" title="Penyesuaian Stok" class="btn btn-ghost p-1.5"><?= icon('sliders', 'w-4 h-4') ?></button>
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

<div id="barangModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeBarangModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <div>
                <h3 id="barangModalTitle" class="font-semibold text-slate-800">Tambah Barang</h3>
                <p id="barangModalSubtitle" class="text-xs text-slate-500">Biarkan kode/barcode kosong untuk otomatis.</p>
            </div>
            <button type="button" onclick="closeBarangModal()" class="btn btn-ghost p-2"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" id="formBarangModal" action="<?= $storeCreateUrl ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
            <?= csrf_field() ?>
            <div>
                <label class="label">Kode Barang <span id="labelKodeReq"></span></label>
                <input type="text" name="kode" id="bm_kode" class="input" placeholder="Kosongkan = otomatis">
                <p id="bm_kode_hint" class="text-xs text-slate-400 mt-1">Biarkan kosong untuk kode otomatis (BRG00001).</p>
            </div>
            <div>
                <label class="label">Barcode</label>
                <input type="text" name="barcode" id="bm_barcode" class="input" placeholder="Scan / biarkan kosong = otomatis">
                <p class="text-xs text-slate-400 mt-1">Scan barcode: jika sudah ada, tawarkan buka &amp; ubah.</p>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Nama Barang *</label>
                <input type="text" name="name" id="bm_name" class="input" required>
            </div>
            <div>
                <label class="label">Kategori</label>
                <select name="category_id" id="bm_category_id" class="input">
                    <option value="">- Pilih -</option>
                    <?php foreach ($kategori as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= e($k['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Satuan *</label>
                <select name="satuan" id="bm_satuan" class="input" required>
                    <?php foreach ($daftarSatuanModal as $s): ?>
                        <option value="<?= e($s) ?>"><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Harga Beli *</label>
                <input type="text" name="harga_beli" id="bm_harga_beli" class="input" inputmode="numeric" required>
            </div>
            <div>
                <label class="label">Harga Jual *</label>
                <input type="text" name="harga_jual" id="bm_harga_jual" class="input" inputmode="numeric" required>
            </div>
            <div>
                <label class="label">Stok Awal</label>
                <input type="number" name="stock_awal" id="bm_stock_awal" class="input" min="0" step="0.01">
                <p id="bm_stock_hint" class="text-xs text-slate-400 mt-1"></p>
            </div>
            <div>
                <label class="label">Stok Minimum *</label>
                <input type="number" name="stock_minimum" id="bm_stock_minimum" class="input" min="0" step="0.01" required>
            </div>
            <div>
                <label class="label">Supplier</label>
                <select name="supplier_id" id="bm_supplier_id" class="input">
                    <option value="">- Pilih -</option>
                    <?php foreach ($supplierList as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Status</label>
                <select name="is_active" id="bm_is_active" class="input">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeBarangModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="adjustModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeAdjustModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg">
        <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-800">Penyesuaian Stok</h3>
                <p id="adjustModalInfo" class="text-sm text-slate-500"></p>
            </div>
            <button type="button" onclick="closeAdjustModal()" class="btn btn-ghost p-2"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" id="formAdjustModal" action="" class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
            <?= csrf_field() ?>
            <div>
                <label class="label">Jenis Penyesuaian</label>
                <select name="jenis" id="am_jenis" class="input">
                    <option value="masuk">Stok Masuk (Bertambah)</option>
                    <option value="keluar">Stok Keluar (Berkurang)</option>
                </select>
            </div>
            <div>
                <label class="label">Jumlah *</label>
                <input type="number" name="qty" id="am_qty" class="input" min="0.01" step="0.01" required>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Keterangan *</label>
                <textarea name="keterangan" id="am_keterangan" class="input" rows="2" placeholder="Contoh: barang rusak, stok opname, kehilangan" required></textarea>
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2">
                <button type="button" onclick="closeAdjustModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Penyesuaian</button>
            </div>
        </form>
    </div>
</div>

<script>
var _storeCreateUrl = <?= json_encode($storeCreateUrl) ?>;
var _storeEditTpl = <?= json_encode($storeEditTpl) ?>;
var _adjustTpl = <?= json_encode($adjustTpl) ?>;
var _cariBarcodeUrl = <?= json_encode($cariBarcodeUrl) ?>;

function openBarangModal(data){
    var modal = document.getElementById('barangModal');
    var form = document.getElementById('formBarangModal');
    var title = document.getElementById('barangModalTitle');
    var hint = document.getElementById('bm_kode_hint');
    var stockInput = document.getElementById('bm_stock_awal');
    var stockHint = document.getElementById('bm_stock_hint');
    if(!data){
        title.textContent = 'Tambah Barang';
        form.action = _storeCreateUrl;
        form.reset();
        document.getElementById('bm_satuan').value = 'pcs';
        document.getElementById('bm_is_active').value = '1';
        document.getElementById('bm_kode').placeholder = 'Kosongkan = otomatis';
        document.getElementById('bm_kode').required = false;
        hint.textContent = 'Biarkan kosong untuk kode otomatis (BRG00001).';
        stockInput.disabled = false;
        stockInput.placeholder = '';
        stockHint.textContent = '';
    } else {
        title.textContent = 'Ubah Barang';
        form.action = _storeEditTpl.replace('__ID__', data.id);
        document.getElementById('bm_kode').value = data.kode || '';
        document.getElementById('bm_kode').required = true;
        document.getElementById('bm_kode').placeholder = '';
        hint.textContent = '';
        document.getElementById('bm_barcode').value = data.barcode || '';
        document.getElementById('bm_name').value = data.name || '';
        document.getElementById('bm_category_id').value = data.category_id || '';
        var satuan = (data.satuan || 'pcs').trim();
        var sel = document.getElementById('bm_satuan');
        var exists = Array.from(sel.options).some(function(o){ return o.value===satuan; });
        if(!exists && satuan){
            var opt=document.createElement('option'); opt.value=satuan; opt.textContent=satuan; sel.appendChild(opt);
        }
        sel.value = satuan;
        document.getElementById('bm_harga_beli').value = data.harga_beli || '';
        document.getElementById('bm_harga_jual').value = data.harga_jual || '';
        stockInput.value = '';
        stockInput.disabled = true;
        stockHint.textContent = 'Stok dikelola lewat pembelian, penjualan, atau penyesuaian.';
        document.getElementById('bm_stock_minimum').value = data.stock_minimum || 0;
        document.getElementById('bm_supplier_id').value = data.supplier_id || '';
        document.getElementById('bm_is_active').value = String(data.is_active ?? 1);
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow='hidden';
    setTimeout(function(){ document.getElementById('bm_name').focus(); }, 50);
}
function closeBarangModal(){
    var modal=document.getElementById('barangModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow='';
}
function openAdjustModal(data){
    var modal=document.getElementById('adjustModal');
    var form=document.getElementById('formAdjustModal');
    var info=document.getElementById('adjustModalInfo');
    form.action=_adjustTpl.replace('__ID__', data.id);
    var stok=parseFloat(data.stock||0);
    var satuan=data.satuan||'';
    var min=parseFloat(data.stock_minimum||0);
    var cls= stok <= min ? 'text-red-600' : '';
    info.innerHTML = eHtml(data.name) + ' — Stok: <b class="'+cls+'">'+ formatAngka(stok)+' '+eHtml(satuan)+'</b>';
    document.getElementById('am_jenis').value='masuk';
    document.getElementById('am_qty').value='';
    document.getElementById('am_keterangan').value='';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow='hidden';
}
function closeAdjustModal(){
    var m=document.getElementById('adjustModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.style.overflow='';
}
function eHtml(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function formatAngka(n){ return Number(n).toLocaleString('id-ID'); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){
        closeBarangModal();
        closeAdjustModal();
    }
});

(function(){
    var inputBarcode=document.getElementById('bm_barcode');
    if(!inputBarcode) return;
    var userTyped=false, typingTimer=null;
    inputBarcode.addEventListener('keydown', function(){ clearTimeout(typingTimer); typingTimer=setTimeout(function(){ userTyped=true; }, 500); });
    inputBarcode.addEventListener('change', function(){
        var v=this.value.trim();
        if(v==='') return;
        var isScan=!userTyped || v.length>=8;
        userTyped=false;
        if(!isScan) return;
        fetch(_cariBarcodeUrl + '?barcode=' + encodeURIComponent(v))
            .then(function(r){ return r.json(); })
            .then(function(res){
                if(res.found){
                    var p=res.produk;
                    Swal.fire({ icon:'info', title:'Barang sudah ada', text: p.kode+' - '+p.name+' (stok '+p.stock+')', showCancelButton:true, confirmButtonText:'Buka & Ubah', cancelButtonText:'Tetap Baru', confirmButtonColor:'#059669' }).then(function(r){
                        if(r.isConfirmed){ openBarangModal({ id:p.id, kode:p.kode, barcode:p.barcode, name:p.name, category_id:'', satuan:p.satuan||'pcs', harga_beli:'', harga_jual:p.harga_jual||'', stock_minimum:'', supplier_id:'', is_active:String(p.is_active??1)}); fetch(_cariBarcodeUrl+'/../..'); }
                    });
                } else {
                    Swal.fire({ icon:'success', title:'Barang baru', text:'Barcode tidak ditemukan. Silakan lengkapi data barang baru.', timer:2500, showConfirmButton:false });
                    document.getElementById('bm_name').focus();
                }
            }).catch(function(){ Swal.fire({ icon:'error', title:'Gagal', text:'Tidak dapat memeriksa barcode.', confirmButtonText:'OK' }); });
    });
    inputBarcode.addEventListener('keydown', function(e){ if(e.key==='Enter') e.preventDefault(); });
})();

<?php if (has_role('Administrator')): ?>
document.addEventListener('DOMContentLoaded', function () {
    var checkAll = document.getElementById('checkAll');
    if (!checkAll) return;
    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(function (c) { c.checked = checkAll.checked; });
    });
});
<?php endif; ?>
</script>
