<?php $canCreate = has_role('Administrator') || has_role('Bendahara'); ?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('pembelian') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="pembelian">
            <input type="date" name="dari" value="<?= e($dari) ?>" class="input w-full md:w-40">
            <input type="date" name="sampai" value="<?= e($sampai) ?>" class="input w-full md:w-40">
            <div class="relative flex-1">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nomor / supplier...">
            </div>
            <select name="status" class="input w-full md:w-36">
                <?php if (!empty($isHistory)): ?>
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                <?php endif; ?>
                <option value="AKTIF" <?= $status === 'AKTIF' ? 'selected' : '' ?>>Aktif</option>
                <option value="DIBATALKAN" <?= $status === 'DIBATALKAN' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
        </form>
        <?php if ($canCreate): ?>
            <a href="<?= url('pembelian', ['action' => 'create']) ?>" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Pembelian Baru</a>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Supplier</th><th class="text-right">Total</th><th>Metode</th><th>Status</th><?php if (!empty($isHistory)): ?><th>Alasan Batal</th><?php endif; ?><th>User</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="<?= !empty($isHistory) ? 9 : 8 ?>" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
            <?php else: foreach ($pg['items'] as $t): ?>
                <tr class="<?= $t['status'] === 'DIBATALKAN' ? 'opacity-50' : '' ?>">
                    <td class="font-mono text-xs"><?= e($t['no_transaksi']) ?></td>
                    <td class="whitespace-nowrap"><?= tanggal($t['tanggal']) ?></td>
                    <td><?= e($t['supplier'] ?? '-') ?></td>
                    <td class="text-right font-semibold whitespace-nowrap"><?= rupiah($t['total']) ?></td>
                    <td><span class="text-[11px] px-2 py-0.5 rounded-full <?= $t['payment_method'] === 'kredit' ? 'bg-orange-100 text-orange-700' : 'bg-emerald-100 text-emerald-700' ?> font-medium"><?= e($t['payment_method']) ?></span></td>
                    <td>
                        <?php if ($t['status'] === 'AKTIF'): ?>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">Aktif</span>
                        <?php else: ?>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium" title="<?= e($t['alasan_batal']) ?>">Dibatalkan</span>
                        <?php endif; ?>
                    </td>
                    <?php if (!empty($isHistory)): ?>
                        <td class="text-xs text-slate-500 max-w-[160px] truncate"><?= e($t['alasan_batal'] ?? '-') ?></td>
                    <?php endif; ?>
                    <td class="text-xs text-slate-500"><?= e($t['username']) ?></td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= url('pembelian', ['action' => 'show', 'id' => $t['id']]) ?>" class="btn btn-ghost p-1.5" title="Detail"><?= icon('eye', 'w-4 h-4') ?></a>
                            <?php if (has_role('Administrator') || has_role('Bendahara')): ?>
                                <a href="<?= url('pembelian', ['action' => 'edit', 'id' => $t['id']]) ?>" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></a>
                            <?php endif; ?>
                            <?php if (has_role('Administrator') && $t['status'] === 'AKTIF'): ?>
                                <form method="post" action="<?= url('pembelian', ['action' => 'destroy', 'id' => $t['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" onclick="return appConfirmSubmit(event, 'Hapus permanen pembelian ini? Data tidak bisa dikembalikan.', 'Hapus Permanen')" title="Hapus Permanen"><?= icon('trash', 'w-4 h-4') ?></button>
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