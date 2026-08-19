<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="mb-4 text-sm text-slate-500">Tahun Ajaran Aktif: <b class="text-emerald-600"><?= e($tahunAjaran) ?></b></div>
        <div class="mb-4 px-4 py-3 rounded-lg bg-slate-50 border border-slate-200 text-sm">
            Saldo kas saat ini: <b class="<?= $saldoKas >= 0 ? 'text-emerald-600' : 'text-red-600' ?>"><?= rupiah($saldoKas) ?></b>
        </div>

        <div class="border border-amber-200 bg-amber-50 text-amber-800 rounded-lg px-4 py-3 text-sm mb-4">
            Saldo awal adalah angka kas yang sudah ada sebelum pencatatan dimulai.
            Perubahan saldo awal hanya boleh dilakukan Administrator dan tercatat dalam log aktivitas.
        </div>

        <form method="post" action="<?= url('kas', ['tab' => 'saldo']) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <div>
                <label class="label">Tanggal</label>
                <input type="date" name="tanggal" class="input" value="<?= e($saldoAwal['tanggal'] ?? date('Y-m-d')) ?>" required max="9999-12-31">
            </div>
            <div>
                <label class="label">Nominal *</label>
                <input type="number" name="nominal" class="input" min="0" step="0.01" value="<?= e((float)($saldoAwal['nominal'] ?? 0)) ?>" required>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Keterangan</label>
                <input type="text" name="keterangan" class="input" value="<?= e($saldoAwal['keterangan'] ?? 'Saldo awal koperasi') ?>">
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2">
                <a href="<?= url('kas') ?>" class="btn btn-secondary">Buka Buku Kas</a>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Saldo Awal</button>
            </div>
        </form>
    </div>
</div>