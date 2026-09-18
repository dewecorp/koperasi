<?php
$storeUrl = url('pemasukan', ['action' => 'store']);
$updateTpl = url('pemasukan', ['action' => 'update', 'id' => '__ID__']);
$canEdit = has_role('Administrator') || has_role('Bendahara');
?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('pemasukan') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="pemasukan">
            <input type="date" name="dari" value="<?= e($dari) ?>" class="input w-full md:w-40">
            <input type="date" name="sampai" value="<?= e($sampai) ?>" class="input w-full md:w-40">
            <div class="relative flex-1">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nomor / keterangan...">
            </div>
        </form>
        <div class="flex items-center gap-3 shrink-0">
            <div class="text-sm font-medium">Total periode: <b class="text-emerald-600"><?= rupiah(array_sum(array_column($pg['items'], 'total'))) ?></b></div>
            <?php if ($canEdit): ?>
            <button type="button" onclick="openPemasukanModal(null)" class="btn btn-primary whitespace-nowrap"><?= icon('plus', 'w-4 h-4') ?> Tambah Pemasukan</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Kategori</th><th>Sumber</th><th>Keterangan</th><th class="text-right">Nominal</th><th>User</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="8" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
            <?php else: foreach ($pg['items'] as $t): ?>
                <?php $rowJson = htmlspecialchars(json_encode($t, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                <tr class="<?= $t['status'] === 'DIBATALKAN' ? 'opacity-50' : '' ?>">
                    <td class="font-mono text-xs"><?= e($t['no_transaksi']) ?></td>
                    <td class="whitespace-nowrap"><?= tanggal($t['tanggal']) ?></td>
                    <td><?= e($t['kategori'] ?? '-') ?></td>
                    <td><?= e($t['sumber'] ?? '-') ?></td>
                    <td class="max-w-[180px] truncate"><?= e($t['keterangan']) ?></td>
                    <td class="text-right font-semibold text-emerald-600 whitespace-nowrap"><?= rupiah($t['total']) ?></td>
                    <td class="text-xs text-slate-500"><?= e($t['username']) ?></td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= url('pemasukan', ['action' => 'show', 'id' => $t['id']]) ?>" class="btn btn-ghost p-1.5" title="Detail"><?= icon('eye', 'w-4 h-4') ?></a>
                            <?php if ($canEdit): ?>
                                <button type="button" onclick="openPemasukanModal(JSON.parse(this.dataset.row))" data-row="<?= $rowJson ?>" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></button>
                            <?php endif; ?>
                            <?php if (has_role('Administrator') && $t['status'] === 'AKTIF'): ?>
                                <form method="post" action="<?= url('pemasukan', ['action' => 'destroy', 'id' => $t['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" onclick="return appConfirmSubmit(event, 'Hapus pemasukan ini? Data tidak bisa dikembalikan.', 'Hapus')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
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

<div id="pemasukanModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closePemasukanModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <div>
                <h3 id="pemasukanModalTitle" class="font-semibold text-slate-800">Tambah Pemasukan</h3>
                <p class="text-xs text-slate-500">Perubahan akan ikut mengubah catatan kas.</p>
            </div>
            <button type="button" onclick="closePemasukanModal()" class="btn btn-ghost p-2"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" id="formPemasukanModal" action="<?= $storeUrl ?>" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
            <?= csrf_field() ?>
            <div>
                <label class="label">Tanggal *</label>
                <input type="date" name="tanggal" id="pm_tanggal" class="input" required max="9999-12-31">
            </div>
            <div>
                <label class="label">Kategori *</label>
                <select name="category_id" id="pm_category_id" class="input" required>
                    <option value="">- Pilih -</option>
                    <?php foreach ($kategori as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= e($k['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Nominal *</label>
                <input type="text" name="nominal" id="pm_nominal" class="input" inputmode="numeric" required placeholder="0">
            </div>
            <div>
                <label class="label">Sumber / Darimana</label>
                <input type="text" name="sumber" id="pm_sumber" class="input" placeholder="Contoh: SPP, jasa fotokopi...">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Keterangan</label>
                <input type="text" name="keterangan" id="pm_keterangan" class="input" placeholder="Keterangan...">
            </div>
            <div id="pm_bukti_wrap">
                <label class="label">Bukti Transaksi (opsional)</label>
                <input type="file" name="bukti" id="pm_bukti" class="input" accept=".jpg,.jpeg,.png,.pdf">
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
                <button type="button" onclick="closePemasukanModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" id="pm_submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
var _pemasukanStoreUrl = <?= json_encode($storeUrl) ?>;
var _pemasukanUpdateTpl = <?= json_encode($updateTpl) ?>;
function openPemasukanModal(data){
    var modal=document.getElementById('pemasukanModal');
    var form=document.getElementById('formPemasukanModal');
    var title=document.getElementById('pemasukanModalTitle');
    var buktiWrap=document.getElementById('pm_bukti_wrap');
    var submitBtn=document.getElementById('pm_submit');
    if(!data){
        title.textContent='Tambah Pemasukan';
        form.action=_pemasukanStoreUrl;
        form.reset();
        document.getElementById('pm_tanggal').value=new Date().toISOString().slice(0,10);
        buktiWrap.classList.remove('hidden');
        submitBtn.disabled=false;
        submitBtn.classList.remove('opacity-50','pointer-events-none');
    } else {
        title.textContent='Ubah Pemasukan';
        form.action=_pemasukanUpdateTpl.replace('__ID__', data.id);
        document.getElementById('pm_tanggal').value=(data.tanggal||'').slice(0,10);
        document.getElementById('pm_category_id').value=data.category_id||'';
        document.getElementById('pm_nominal').value=data.total||'';
        document.getElementById('pm_sumber').value=data.sumber||'';
        document.getElementById('pm_keterangan').value=data.keterangan||'';
        buktiWrap.classList.add('hidden');
        var isBatal=data.status==='DIBATALKAN';
        submitBtn.disabled=isBatal;
        submitBtn.classList.toggle('opacity-50',isBatal);
        submitBtn.classList.toggle('pointer-events-none',isBatal);
        submitBtn.title=isBatal?'Transaksi dibatalkan tidak dapat diubah':'';
    }
    if(document.getElementById('pm_nominal')) formatNominal(document.getElementById('pm_nominal'));
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow='hidden';
    setTimeout(function(){ document.getElementById('pm_tanggal').focus(); },50);
}
function closePemasukanModal(){
    var m=document.getElementById('pemasukanModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.style.overflow='';
}
function formatNominal(el){
    if(!el||!el.value) return;
    var v=el.value.replace(/[^0-9]/g,'');
    if(v==='') return;
    el.value=Number(v).toLocaleString('id-ID');
}
function openPemasukanEditModal(data){ openPemasukanModal(data); }
(function(){
    var el=document.getElementById('pm_nominal');
    if(el){
        el.addEventListener('input', function(){
            var raw=this.value.replace(/[^0-9]/g,'');
            if(raw===''){ this.value=''; return; }
            this.value=Number(raw).toLocaleString('id-ID');
        });
        el.addEventListener('blur', function(){ formatNominal(this); });
    }
    var form=document.getElementById('formPemasukanModal');
    if(form) form.addEventListener('submit', function(){
        var inp=this.querySelector('input[name="nominal"]');
        if(inp) inp.value=inp.value.replace(/\./g,'').replace(/,/g,'.');
    });
})();
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closePemasukanModal(); });
</script>
