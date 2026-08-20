<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Pengaturan Aplikasi</h2>
        <p class="text-sm text-slate-500 mb-5">Pengaturan ini hanya dapat diubah oleh Administrator.</p>

        <form method="post" action="<?= url('pengaturan') ?>" class="space-y-4">
            <?= csrf_field() ?>
            
            <div class="p-4 border border-slate-200 rounded-xl">
                <label class="label">Tahun Ajaran Aktif</label>
                <select name="tahun_ajaran_aktif" class="input">
                    <?php
                    $allTahun = array_merge($tahunOptions, array_column($tahunBerisi, 'tahun_ajaran'));
                    $allTahun = array_values(array_unique($allTahun));
                    sort($allTahun);
                    ?>
                    <?php foreach ($allTahun as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $set['tahun_ajaran_aktif'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-slate-500 mt-1">Data transaksi mengikuti tahun ajaran aktif. Saat pindah ke tahun ajaran baru, saldo kas tahun sebelumnya otomatis dibawa sebagai saldo awal dengan keterangan "Saldo kas tahun ajaran ..."</p>
            </div>

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
                <input type="text" name="saldo_minimum_cash" class="input" inputmode="numeric" min="0" step="0.01" value="<?= e($set['saldo_minimum_cash']) ?>">
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    <?php
    $tahunHapusList = array_filter(
        array_merge($tahunOptions, array_column($tahunBerisi, 'tahun_ajaran')),
        fn($t) => $t !== $set['tahun_ajaran_aktif']
    );
    $tahunHapusList = array_values(array_unique($tahunHapusList));
    sort($tahunHapusList);
    ?>

    <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6">
        <h2 class="font-semibold text-red-700 mb-1">Hapus Data per Tahun Ajaran</h2>
        <p class="text-sm text-slate-500 mb-4">Hapus seluruh transaksi (penjualan, pembelian, kas, piutang, hutang, stok) pada satu tahun ajaran untuk mengosongkan database. Data yang dihapus <b>tidak dapat dikembalikan</b>. Tahun ajaran aktif tidak dapat dihapus.</p>

        <?php if (empty($tahunHapusList)): ?>
            <div class="text-sm text-slate-400">Belum ada data tahun ajaran untuk dihapus.</div>
        <?php else: ?>
            <form method="post" action="<?= url('pengaturan', ['action' => 'hapusTahunAjaran']) ?>" class="flex flex-col sm:flex-row gap-2 items-end">
                <?= csrf_field() ?>
                <div class="flex-1 w-full">
                    <label class="label">Tahun Ajaran</label>
                    <select name="tahun_ajaran_hapus" class="input" required>
                        <option value="">- Pilih tahun ajaran -</option>
                        <?php foreach ($tahunHapusList as $t): ?>
                            <?php $jml = 0; foreach ($tahunBerisi as $tb) if ($tb['tahun_ajaran'] === $t) $jml = (int)$tb['jml']; ?>
                            <option value="<?= e($t) ?>"><?= e($t) ?> <?= $jml > 0 ? '(' . $jml . ' transaksi)' : '(kosong)' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn bg-red-600 text-white hover:bg-red-700" onclick="return appConfirmSubmit(event, 'Seluruh data transaksi tahun ajaran ini akan dihapus permanen dan tidak bisa dikembalikan. Lanjutkan?', 'Hapus Data')"><?= icon('trash', 'w-4 h-4') ?> Hapus Data</button>
            </form>
        <?php endif; ?>
    </div>
</div>