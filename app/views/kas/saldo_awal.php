<div class="w-full">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4">
            <div>
                <div class="text-sm text-slate-500">Tahun Ajaran Aktif: <b class="text-emerald-600"><?= e($tahunAjaran) ?></b></div>
                <div class="mt-2 px-4 py-3 rounded-lg bg-slate-50 border border-slate-200 text-sm">
                    Saldo kas saat ini: <b class="<?= $saldoKas >= 0 ? 'text-emerald-600' : 'text-red-600' ?>"><?= rupiah($saldoKas) ?></b>
                </div>
            </div>
            <button type="button" onclick="openSaldoAwalModal()" class="btn btn-primary shrink-0"><?= icon('edit', 'w-4 h-4') ?> Ubah Saldo Awal</button>
        </div>

        <div class="border border-amber-200 bg-amber-50 text-amber-800 rounded-lg px-4 py-3 text-sm mb-4">
            Saldo awal adalah angka kas yang sudah ada sebelum pencatatan dimulai.
            Perubahan saldo awal hanya boleh dilakukan Administrator dan tercatat dalam log aktivitas.
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Tanggal saldo awal</div><div class="font-medium text-slate-800 mt-1"><?= e($saldoAwal['tanggal'] ?? '-') ?></div></div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl"><div class="text-xs text-slate-500">Nominal</div><div class="font-medium text-slate-800 mt-1"><?= rupiah((float)($saldoAwal['nominal'] ?? 0)) ?></div></div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl sm:col-span-1 col-span-1"><div class="text-xs text-slate-500">Keterangan</div><div class="font-medium text-slate-800 mt-1 truncate"><?= e($saldoAwal['keterangan'] ?? '-') ?></div></div>
        </div>
        <div class="flex justify-end mt-4">
            <a href="<?= url('kas') ?>" class="btn btn-secondary">Buka Buku Kas</a>
        </div>
    </div>
</div>

<div id="saldoAwalModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeSaldoAwalModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800">Ubah Saldo Awal</h3>
                <button type="button" onclick="closeSaldoAwalModal()" class="h-8 w-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500">&times;</button>
            </div>
            <form method="post" action="<?= url('kas', ['tab' => 'saldo']) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?= csrf_field() ?>
                <div>
                    <label class="label">Tanggal</label>
                    <input type="date" name="tanggal" class="input" value="<?= e($saldoAwal['tanggal'] ?? date('Y-m-d')) ?>" required max="9999-12-31">
                </div>
                <div>
                    <label class="label">Nominal *</label>
                    <input type="text" name="nominal" class="input" inputmode="numeric" min="0" step="0.01" value="<?= e((float)($saldoAwal['nominal'] ?? 0)) ?>" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Keterangan</label>
                    <input type="text" name="keterangan" class="input" value="<?= e($saldoAwal['keterangan'] ?? 'Saldo awal koperasi') ?>">
                </div>
                <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeSaldoAwalModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Saldo Awal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openSaldoAwalModal(){ document.getElementById('saldoAwalModal').classList.remove('hidden'); }
function closeSaldoAwalModal(){ document.getElementById('saldoAwalModal').classList.add('hidden'); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeSaldoAwalModal(); });
</script>
<noscript>
<div class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <form method="post" action="<?= url('kas', ['tab' => 'saldo']) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <div><label class="label">Tanggal</label><input type="date" name="tanggal" class="input" value="<?= e($saldoAwal['tanggal'] ?? date('Y-m-d')) ?>" required></div>
        <div><label class="label">Nominal *</label><input type="text" name="nominal" class="input" value="<?= e((float)($saldoAwal['nominal'] ?? 0)) ?>" required></div>
        <div class="sm:col-span-2"><label class="label">Keterangan</label><input type="text" name="keterangan" class="input" value="<?= e($saldoAwal['keterangan'] ?? 'Saldo awal koperasi') ?>"></div>
        <div class="sm:col-span-2 flex justify-end gap-2"><button type="submit" class="btn btn-primary">Simpan Saldo Awal</button></div>
    </form>
</div>
</noscript>
