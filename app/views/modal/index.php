<?php
$jenis = [
    'modal_awal' => ['Modal Awal', 'bg-sky-100 text-sky-700'],
    'tambahan' => ['Tambahan Modal', 'bg-emerald-100 text-emerald-700'],
    'pengurangan' => ['Pengurangan Modal', 'bg-red-100 text-red-700'],
];
$modalTotal = (float)db()->query('SELECT COALESCE(SUM(CASE WHEN type="modal_awal" OR type="tambahan" THEN nominal WHEN type="pengurangan" THEN -nominal END),0) FROM capital_transactions WHERE status="AKTIF"')->fetchColumn();

$hasEdit = has_role('Administrator') || has_role('Bendahara');
?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('modal') ?>" class="js-filter-form flex gap-2 flex-1 max-w-md">
            <input type="hidden" name="page" value="modal">
            <div class="relative flex-1">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nomor / keterangan...">
            </div>
        </form>
        <div class="text-sm font-medium">Total Modal Aktif: <b class="text-slate-800"><?= rupiah($modalTotal) ?></b></div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Jenis</th><th class="text-right">Nominal</th><th>Keterangan</th><th>User</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="7" class="text-center text-slate-400 py-8">Belum ada transaksi modal.</td></tr>
            <?php else: foreach ($pg['items'] as $t): ?>
                <tr>
                    <td class="font-mono text-xs"><?= e($t['no_transaksi']) ?></td>
                    <td class="whitespace-nowrap"><?= tanggal($t['tanggal']) ?></td>
                    <td><span class="text-[11px] px-2 py-0.5 rounded-full font-medium <?= $jenis[$t['type']][1] ?>"><?= e($jenis[$t['type']][0]) ?></span></td>
                    <td class="text-right font-semibold <?= $t['type'] === 'pengurangan' ? 'text-red-600' : 'text-emerald-600' ?> whitespace-nowrap"><?php echo $t['type'] === 'pengurangan' ? '-' : '+'; ?> <?= rupiah($t['nominal']) ?></td>
                    <td><?= e($t['keterangan'] ?: '-') ?></td>
                    <td class="text-xs text-slate-500"><?= e($t['username'] ?? '-') ?></td>
                    <td>
                        <?php if (has_role('Administrator')): ?>
                            <form method="post" action="<?= url('modal', ['action' => 'cancel', 'id' => $t['id']]) ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-ghost p-1.5" onclick="return appConfirmSubmit(event, 'Batalkan transaksi modal ini?')" title="Batalkan"><?= icon('x', 'w-4 h-4') ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_links($pg) ?>
</div>

<?php if ($hasEdit): ?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <h2 class="font-semibold text-slate-800 mb-4">Catat Transaksi Modal</h2>
    <p class="text-xs text-slate-500 mb-4"><b>Catatan:</b> Modal dicatat terpisah dari pendapatan &amp; pengeluaran operasional, dan tidak memengaruhi saldo kas.</p>
    <form method="post" action="<?= url('modal', ['action' => 'store']) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <div>
            <label class="label">Tanggal *</label>
            <input type="date" name="tanggal" class="input" value="<?= e(old('tanggal', date('Y-m-d'))) ?>" required max="9999-12-31">
        </div>
        <div>
            <label class="label">Jenis *</label>
            <select name="type" class="input">
                <option value="modal_awal" <?= old('type') === 'modal_awal' ? 'selected' : '' ?>>Modal Awal</option>
                <option value="tambahan" <?= old('type') === 'tambahan' || old('type') === '' ? 'selected' : '' ?>>Tambahan Modal</option>
                <option value="pengurangan" <?= old('type') === 'pengurangan' ? 'selected' : '' ?>>Pengurangan Modal</option>
            </select>
        </div>
        <div>
            <label class="label">Nominal *</label>
            <input type="text" name="nominal" class="input" inputmode="numeric" min="1" step="0.01" value="<?= e(old('nominal')) ?>" required>
        </div>
        <div>
            <label class="label">Keterangan</label>
            <input type="text" name="keterangan" class="input" value="<?= e(old('keterangan')) ?>">
        </div>
        <div class="sm:col-span-2 flex justify-end pt-2">
            <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
        </div>
    </form>
</div>
<?php endif; ?>