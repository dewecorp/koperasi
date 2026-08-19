<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Pengaturan Aplikasi</h2>
        <p class="text-sm text-slate-500 mb-5">Pengaturan ini hanya dapat diubah oleh Administrator.</p>

        <form method="post" action="<?= url('pengaturan') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div class="flex items-start justify-between gap-4 p-4 border border-slate-200 rounded-xl">
                <div>
                    <div class="font-medium text-slate-800">Izinkan Saldo Kas Negatif</div>
                    <p class="text-sm text-slate-500">Jika dimatikan, pengeluaran atau pembayaran hutang yang melebihi saldo kas ditolak.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" name="allow_negative_cash" value="1" class="sr-only peer" <?= $set['allow_negative_cash'] === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-slate-300 peer-checked:bg-emerald-600 rounded-full transition relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition peer-checked:after:translate-x-5"></div>
                </label>
            </div>

            <div class="flex items-start justify-between gap-4 p-4 border border-slate-200 rounded-xl">
                <div>
                    <div class="font-medium text-slate-800">Izinkan Stok Negatif</div>
                    <p class="text-sm text-slate-500">Jika dimatikan, penjualan dengan stok tidak cukup ditolak.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" name="allow_negative_stock" value="1" class="sr-only peer" <?= $set['allow_negative_stock'] === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-slate-300 peer-checked:bg-emerald-600 rounded-full transition relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition peer-checked:after:translate-x-5"></div>
                </label>
            </div>

            <div class="p-4 border border-slate-200 rounded-xl">
                <label class="label">Batas Minimum Saldo Kas (indikator dashboard)</label>
                <input type="number" name="saldo_minimum_cash" class="input" min="0" step="0.01" value="<?= e($set['saldo_minimum_cash']) ?>">
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-3">Informasi Sistem</h2>
        <dl class="text-sm space-y-2">
            <div class="flex justify-between"><dt class="text-slate-500">Aplikasi</dt><dd><?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">PHP</dt><dd><?= e(PHP_VERSION) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Database</dt><dd><?= e(DB_NAME) ?> (<?= e(DB_HOST) ?>)</dd></div>
        </dl>
    </div>
</div>