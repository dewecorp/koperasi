<?php ?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row gap-3 justify-between mb-4">
        <form method="get" action="<?= url('log') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="log">
            <div class="relative flex-1 max-w-md">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari user / aktivitas / detail...">
            </div>
            <input type="date" name="dari" value="<?= e($dari) ?>" class="input w-full md:w-40">
            <input type="date" name="sampai" value="<?= e($sampai) ?>" class="input w-full md:w-40">
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Waktu</th><th>User</th><th>Aktivitas</th><th>Detail</th><th>IP</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="5" class="text-center text-slate-400 py-8">Tidak ada log.</td></tr>
            <?php else: foreach ($pg['items'] as $l): ?>
                <tr>
                    <td class="whitespace-nowrap text-xs"><?= tanggal_waktu($l['created_at']) ?></td>
                    <td class="font-mono text-xs"><?= e($l['username']) ?></td>
                    <td><span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium"><?= e($l['aktivitas']) ?></span></td>
                    <td class="text-xs text-slate-600 max-w-[300px]"><?= e($l['detail']) ?></td>
                    <td class="font-mono text-xs"><?= e($l['ip_address']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_links($pg) ?>
</div>