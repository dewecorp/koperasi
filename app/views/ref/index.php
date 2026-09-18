<?php
$pageKey = $refLabel;
$editId = (int)input('edit', 0);
$editing = null;
if ($editId) {
    foreach ($pg['items'] as $row) {
        if ((int)$row['id'] === $editId) { $editing = $row; break; }
    }
    if (!$editing) {
        $stmt = db()->prepare('SELECT * FROM ' . ($pageKey === 'supplier' ? 'suppliers' : 'customers') . ' WHERE id = ?');
        $stmt->execute([$editId]);
        $editing = $stmt->fetch() ?: null;
    }
}
?>
<div class="w-full">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
            <form method="get" action="<?= url($pageKey) ?>" class="js-filter-form flex gap-2 flex-1 max-w-md">
                <input type="hidden" name="page" value="<?= e($pageKey) ?>">
                <div class="relative flex-1">
                    <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                    <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nama / telepon...">
                </div>
            </form>
            <button type="button" onclick="openRefModal()" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Tambah <?= e(ucfirst($refLabel)) ?></button>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr>
                    <?php foreach ($fields as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
                    <th>Status</th><th class="text-center">Aksi</th>
                </tr></thead>
                <tbody>
                <?php if (empty($pg['items'])): ?>
                    <tr><td colspan="<?= count($fields) + 2 ?>" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
                <?php else: foreach ($pg['items'] as $row): ?>
                    <tr>
                        <?php foreach (array_keys($fields) as $key): ?>
                            <td><?= e($row[$key] ?? '-') ?></td>
                        <?php endforeach; ?>
                        <td>
                            <?php if ((int)$row['is_active'] === 1): ?>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">Aktif</span>
                            <?php else: ?>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 font-medium">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" onclick="openRefModal(<?= htmlspecialchars(json_encode(['id'=>(int)$row['id'],'name'=>$row['name'],'phone'=>$row['phone']??'','address'=>$row['address']??'']), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></button>
                                <form method="post" action="<?= url($pageKey, ['action' => 'destroy', 'id' => $row['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5" onclick="return appConfirmSubmit(event, 'Hapus data ini?')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_links($pg) ?>
    </div>
</div>

<div id="refModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeRefModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="refModalTitle" class="font-semibold text-slate-800">Tambah <?= e(ucfirst($refLabel)) ?></h3>
                <button type="button" onclick="closeRefModal()" class="h-8 w-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500">&times;</button>
            </div>
            <form id="refForm" method="post" action="<?= url($pageKey, ['action' => 'store']) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?= csrf_field() ?>
                <div class="sm:col-span-2">
                    <label class="label">Nama *</label>
                    <input type="text" name="name" id="refName" class="input" required>
                </div>
                <div>
                    <label class="label">Telepon</label>
                    <input type="text" name="phone" id="refPhone" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Alamat</label>
                    <textarea name="address" id="refAddress" class="input" rows="2"></textarea>
                </div>
                <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeRefModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
                </div>
            </form>
            <noscript><div class="mt-3 text-xs text-amber-600">JavaScript nonaktif — gunakan <a href="<?= url($pageKey, ['q'=>$q,'edit'=>1]) ?>" class="underline">?edit=id</a> fallback.</div></noscript>
        </div>
    </div>
</div>

<script>
var REF_STORE = <?= json_encode(url($pageKey, ['action' => 'store'])) ?>;
var REF_UPDATE_BASE = <?= json_encode(url($pageKey, ['action' => 'update'])) ?>;
function openRefModal(data){
    var modal=document.getElementById('refModal');
    var form=document.getElementById('refForm');
    var title=document.getElementById('refModalTitle');
    var inpN=document.getElementById('refName');
    var inpP=document.getElementById('refPhone');
    var inpA=document.getElementById('refAddress');
    if(data && data.id){
        title.textContent='Ubah <?= e(ucfirst($refLabel)) ?>';
        form.action=REF_UPDATE_BASE+'/'+data.id;
        inpN.value=data.name||'';
        inpP.value=data.phone||'';
        inpA.value=data.address||'';
    } else {
        title.textContent='Tambah <?= e(ucfirst($refLabel)) ?>';
        form.action=REF_STORE;
        inpN.value='';
        inpP.value='';
        inpA.value='';
    }
    modal.classList.remove('hidden');
    setTimeout(function(){ inpN.focus(); },50);
}
function closeRefModal(){ document.getElementById('refModal').classList.add('hidden'); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeRefModal(); });
<?php if ($editing): ?>
openRefModal(<?= json_encode(['id'=>(int)$editing['id'],'name'=>$editing['name'],'phone'=>$editing['phone']??'','address'=>$editing['address']??'']) ?>);
<?php endif; ?>
</script>
