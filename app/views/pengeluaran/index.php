<?php ?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('pengeluaran') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="pengeluaran">
            <input type="date" name="dari" value="<?= e($dari) ?>" class="input w-full md:w-40">
            <input type="date" name="sampai" value="<?= e($sampai) ?>" class="input w-full md:w-40">
            <div class="relative flex-1">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nomor / keterangan...">
            </div>
        </form>
        <div class="text-sm font-medium">
            Total periode: <b class="text-red-600">- <?= rupiah(array_sum(array_column($pg['items'], 'total'))) ?></b>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Kategori</th><th>Penerima</th><th>Keterangan</th><th class="text-right">Nominal</th><th>User</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="8" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
            <?php else: foreach ($pg['items'] as $t): ?>
                <tr class="<?= $t['status'] === 'DIBATALKAN' ? 'opacity-50' : '' ?>">
                    <td class="font-mono text-xs"><?= e($t['no_transaksi']) ?></td>
                    <td class="whitespace-nowrap"><?= tanggal($t['tanggal']) ?></td>
                    <td><?= e($t['kategori'] ?? '-') ?></td>
                    <td><?= e($t['penerima'] ?? '-') ?></td>
                    <td class="max-w-[180px] truncate"><?= e($t['keterangan']) ?></td>
                    <td class="text-right font-semibold text-red-600 whitespace-nowrap"><?= rupiah($t['total']) ?></td>
                    <td class="text-xs text-slate-500"><?= e($t['username']) ?></td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= url('pengeluaran', ['action' => 'show', 'id' => $t['id']]) ?>" class="btn btn-ghost p-1.5" title="Detail"><?= icon('eye', 'w-4 h-4') ?></a>
                            <?php if (has_role('Administrator') || has_role('Bendahara')): ?>
                                <a href="<?= url('pengeluaran', ['action' => 'edit', 'id' => $t['id']]) ?>" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></a>
                            <?php endif; ?>
                            <?php if (has_role('Administrator') && $t['status'] === 'AKTIF'): ?>
                                <form method="post" action="<?= url('pengeluaran', ['action' => 'cancel', 'id' => $t['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" onclick="return confirmCancelForm(event, 'Batalkan pengeluaran?')" title="Hapus (Batalkan)"><?= icon('trash', 'w-4 h-4') ?></button>
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

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <h2 class="font-semibold text-slate-800 mb-4">Catat Pengeluaran</h2>
    <form method="post" action="<?= url('pengeluaran', ['action' => 'store']) ?>" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <div>
            <label class="label">Tanggal *</label>
            <input type="date" name="tanggal" class="input" value="<?= e(old('tanggal', date('Y-m-d'))) ?>" required max="9999-12-31">
        </div>
        <div>
            <label class="label">Kategori *</label>
            <select name="category_id" class="input" required>
                <option value="">- Pilih -</option>
                <?php foreach ($kategori as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= (string)old('category_id') === (string)$k['id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label">Nominal *</label>
            <input type="number" name="nominal" class="input" min="1" step="0.01" value="<?= e(old('nominal')) ?>" required>
        </div>
        <div>
            <label class="label">Penerima / Untuk</label>
            <input type="text" name="penerima" class="input" placeholder="Contoh: PLN, ATK..." value="<?= e(old('penerima')) ?>">
        </div>
        <div>
            <label class="label">Keterangan</label>
            <input type="text" name="keterangan" class="input" value="<?= e(old('keterangan')) ?>">
        </div>
        <div>
            <label class="label">Bukti Transaksi (opsional)</label>
            <input type="file" name="bukti" class="input" accept=".jpg,.jpeg,.png,.pdf">
        </div>
        <div class="sm:col-span-2 flex justify-end pt-2">
            <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Pengeluaran</button>
        </div>
    </form>
</div>