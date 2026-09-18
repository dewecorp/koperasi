<?php
$pdo = db();
$editId = (int)input('edit', 0);
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}
?>
<div class="w-full">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
            <form method="get" action="<?= url('pengguna') ?>" class="js-filter-form flex gap-2 flex-1 max-w-md">
                <input type="hidden" name="page" value="pengguna">
                <div class="relative flex-1">
                    <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                    <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari username / nama...">
                </div>
            </form>
            <button type="button" onclick="openPenggunaModal()" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Tambah Pengguna</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Username</th><th>Nama</th><th>Peran</th><th>Password Awal</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($pg['items'])): ?>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
                <?php else: foreach ($pg['items'] as $u): ?>
                    <tr>
                        <td class="font-mono text-xs"><?= e($u['username']) ?></td>
                        <td><?= e($u['name']) ?></td>
                        <td><span class="text-[11px] px-2 py-0.5 rounded-full <?= $u['role_name'] === 'Administrator' ? 'bg-fuchsia-100 text-fuchsia-700' : ($u['role_name'] === 'Bendahara' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600') ?> font-medium"><?= e($u['role_name']) ?></span></td>
                        <td><?= (int)$u['must_change_password'] === 1 ? '<span class="text-amber-600 text-xs font-medium">WAJIB GANTI</span>' : '<span class="text-emerald-600 text-xs">OK</span>' ?></td>
                        <td>
                            <?php if ((int)$u['is_active'] === 1): ?>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">Aktif</span>
                            <?php else: ?>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 font-medium">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" onclick="openPenggunaModal(<?= htmlspecialchars(json_encode(['id'=>(int)$u['id'],'username'=>$u['username'],'name'=>$u['name'],'role_id'=>(int)$u['role_id'],'is_active'=>(int)$u['is_active']]), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></button>
                                <form method="post" action="<?= url('pengguna', ['action' => 'destroy', 'id' => $u['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5" onclick="return appConfirmSubmit(event, 'Hapus pengguna <?= e($u['username']) ?>?')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
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

<div id="penggunaModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closePenggunaModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 id="penggunaModalTitle" class="font-semibold text-slate-800">Tambah Pengguna</h3>
                <button type="button" onclick="closePenggunaModal()" class="h-8 w-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500">&times;</button>
            </div>
            <form id="penggunaForm" method="post" action="<?= url('pengguna', ['action' => 'store']) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?= csrf_field() ?>
                <div id="penggunaUsernameWrap">
                    <label class="label">Username *</label>
                    <input type="text" name="username" id="penggunaUsername" class="input" value="<?= e(old('username')) ?>" required>
                </div>
                <div>
                    <label class="label">Nama Lengkap *</label>
                    <input type="text" name="name" id="penggunaName" class="input" required>
                </div>
                <div>
                    <label class="label">Peran *</label>
                    <select name="role_id" id="penggunaRole" class="input">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" id="penggunaPasswordLabel">Password *</label>
                    <input type="password" name="password" id="penggunaPassword" class="input" minlength="6" required>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select name="is_active" id="penggunaIsActive" class="input">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                <div class="col-span-full flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closePenggunaModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
                </div>
            </form>
            <noscript><div class="mt-4 text-xs text-amber-600">JavaScript nonaktif — gunakan form fallback di <a href="<?= url('pengguna') ?>" class="underline">halaman pengguna</a> dengan ?edit=id.</div></noscript>
        </div>
    </div>
</div>

<script>
var PENGGUNA_STORE = <?= json_encode(url('pengguna', ['action' => 'store'])) ?>;
var PENGGUNA_UPDATE_BASE = <?= json_encode(url('pengguna', ['action' => 'update'])) ?>;
function openPenggunaModal(data){
    var modal=document.getElementById('penggunaModal');
    var form=document.getElementById('penggunaForm');
    var title=document.getElementById('penggunaModalTitle');
    var wrapU=document.getElementById('penggunaUsernameWrap');
    var inpU=document.getElementById('penggunaUsername');
    var inpN=document.getElementById('penggunaName');
    var selR=document.getElementById('penggunaRole');
    var inpP=document.getElementById('penggunaPassword');
    var labP=document.getElementById('penggunaPasswordLabel');
    var selA=document.getElementById('penggunaIsActive');
    if(data && data.id){
        title.textContent='Ubah Pengguna';
        form.action=PENGGUNA_UPDATE_BASE+'/'+data.id;
        wrapU.style.display='none';
        inpU.required=false;
        inpU.value=data.username||'';
        inpN.value=data.name||'';
        selR.value=String(data.role_id||'');
        selA.value=String(data.is_active!=null?data.is_active:1);
        labP.textContent='Password (kosongkan bila tidak diganti)';
        inpP.required=false;
        inpP.value='';
        inpP.placeholder='Kosongkan bila tidak diganti';
    } else {
        title.textContent='Tambah Pengguna';
        form.action=PENGGUNA_STORE;
        wrapU.style.display='';
        inpU.required=true;
        inpU.value='';
        inpN.value='';
        selR.selectedIndex=0;
        selA.value='1';
        labP.textContent='Password *';
        inpP.required=true;
        inpP.value='';
        inpP.placeholder='';
    }
    modal.classList.remove('hidden');
    setTimeout(function(){ (data&&data.id?inpN:inpU).focus(); },50);
}
function closePenggunaModal(){ document.getElementById('penggunaModal').classList.add('hidden'); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closePenggunaModal(); });
<?php if ($editing): ?>
openPenggunaModal(<?= json_encode(['id'=>(int)$editing['id'],'username'=>$editing['username'],'name'=>$editing['name'],'role_id'=>(int)$editing['role_id'],'is_active'=>(int)$editing['is_active']]) ?>);
<?php endif; ?>
</script>
