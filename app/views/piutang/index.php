<?php
?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('piutang') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="piutang">
            <div class="relative flex-1 max-w-md">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari pelanggan / no transaksi...">
            </div>
            <select name="status" class="input w-full md:w-40">
                <option value="">Semua Status</option>
                <option value="belum" <?= $status === 'belum' ? 'selected' : '' ?>>Belum Lunas</option>
                <option value="sebagian" <?= $status === 'sebagian' ? 'selected' : '' ?>>Sebagian</option>
                <option value="lunas" <?= $status === 'lunas' ? 'selected' : '' ?>>Lunas</option>
            </select>
        </form>
        <?php
        $totSisa = array_sum(array_map(fn($r) => (float)$r['sisa'], $pg['items']));
        ?>
        <div class="text-sm font-medium">Total Sisa Piutang (filter): <b class="text-fuchsia-600"><?= rupiah($totSisa) ?></b></div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Pelanggan</th><th class="text-right">Total</th><th class="text-right">Dibayar</th><th class="text-right">Sisa</th><th>Jatuh Tempo</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="9" class="text-center text-slate-400 py-8">Tidak ada piutang.</td></tr>
            <?php else: foreach ($pg['items'] as $r): list($label, $cls) = status_tagihan((float)$r['dibayar'], (float)$r['sisa']); ?>
                <tr>
                    <td class="font-mono text-xs"><?= e($r['no_transaksi']) ?></td>
                    <td class="whitespace-nowrap"><?= tanggal($r['tanggal']) ?></td>
                    <td><?= e($r['pelanggan']) ?></td>
                    <td class="text-right"><?= rupiah($r['total']) ?></td>
                    <td class="text-right text-emerald-600"><?= rupiah($r['dibayar']) ?></td>
                    <td class="text-right font-bold text-fuchsia-600"><?= rupiah($r['sisa']) ?></td>
                    <td class="whitespace-nowrap <?= $r['jatuh_tempo'] && $r['jatuh_tempo'] < date('Y-m-d') && $r['sisa'] > 0 ? 'text-red-600 font-medium' : '' ?>"><?= tanggal($r['jatuh_tempo']) ?></td>
                    <td><span class="text-[11px] px-2 py-0.5 rounded-full font-medium <?= $cls ?>"><?= e($label) ?></span></td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= url('piutang', ['action' => 'show', 'id' => $r['id']]) ?>" class="btn btn-ghost p-1.5" title="Detail & Bayar"><?= icon('eye', 'w-4 h-4') ?></a>
                            <?php if (has_role('Administrator') || has_role('Bendahara')): ?>
                                <a href="<?= url('piutang', ['action' => 'edit', 'id' => $r['id']]) ?>" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></a>
                            <?php endif; ?>
                            <?php if (has_role('Administrator')): ?>
                                <form method="post" action="<?= url('piutang', ['action' => 'destroy', 'id' => $r['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" onclick="return appConfirmSubmit(event, 'Hapus piutang ini? Data tidak bisa dikembalikan.', 'Hapus')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
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