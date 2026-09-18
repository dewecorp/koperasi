<?php
$editId = (int)input('edit', 0);
$editing = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}
$typeLabel = $types[$type] ?? ucfirst($type);
?>
<div class="w-full">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
            <h2 class="font-semibold text-slate-800 flex items-center gap-2"><?= icon('tag', 'w-5 h-5 text-emerald-600') ?> Kategori <?= e($typeLabel) ?> <span class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full font-medium"><?= angka(count($items)) ?> data</span></h2>
            <button type="button" onclick="openKategoriModal()" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Tambah Kategori</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th class="text-right">Dipakai</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="3" class="text-center text-slate-400 py-8">Belum ada kategori <?= e(strtolower($typeLabel)) ?>.</td></tr>
                <?php else: foreach ($items as $k): ?>
                    <tr>
                        <td class="font-medium text-slate-800"><?= e($k['name']) ?></td>
                        <td class="text-right"><?= angka($k['jumlah_barang']) ?></td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" onclick="openKategoriModal(<?= (int)$k['id'] ?>, <?= htmlspecialchars(json_encode($k['name']), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></button>
                                <form method="post" action="<?= url('kategori', ['action' => 'destroy', 'id' => $k['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="type" value="<?= e($type) ?>">
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-500 hover:text-red-600" onclick="return appConfirmSubmit(event, 'Hapus kategori ini?')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="kategoriModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeKategoriModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="kategoriModalTitle" class="font-semibold text-slate-800">Tambah Kategori</h3>
                <button type="button" onclick="closeKategoriModal()" class="h-8 w-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500">&times;</button>
            </div>
            <form id="kategoriForm" method="post" action="<?= url('kategori', ['action' => 'store']) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="<?= e($type) ?>">
                <div>
                    <label class="label">Nama kategori *</label>
                    <input type="text" name="name" id="kategoriName" class="input" placeholder="Nama kategori..." required>
                    <p class="text-[11px] text-slate-400 mt-1">Tipe: <b><?= e($typeLabel) ?></b></p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeKategoriModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var KATEGORI_TYPE = <?= json_encode($type) ?>;
function openKategoriModal(id, name){
    var modal = document.getElementById('kategoriModal');
    var form = document.getElementById('kategoriForm');
    var title = document.getElementById('kategoriModalTitle');
    var inp = document.getElementById('kategoriName');
    if(id){
        title.textContent = 'Ubah Kategori';
        form.action = '<?= url('kategori', ['action' => 'update']) ?>/' + id;
        inp.value = name || '';
    } else {
        title.textContent = 'Tambah Kategori';
        form.action = '<?= url('kategori', ['action' => 'store']) ?>';
        inp.value = '';
    }
    modal.classList.remove('hidden');
    setTimeout(function(){ inp.focus(); }, 50);
}
function closeKategoriModal(){
    document.getElementById('kategoriModal').classList.add('hidden');
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeKategoriModal(); });
<?php if ($editing): ?>
openKategoriModal(<?= (int)$editing['id'] ?>, <?= json_encode($editing['name']) ?>);
<?php endif; ?>
</script>
