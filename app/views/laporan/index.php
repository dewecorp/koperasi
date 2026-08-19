<?php
$tabs = [
    'kas' => 'Laporan Kas', 'penjualan' => 'Penjualan', 'pembelian' => 'Pembelian',
    'pemasukan' => 'Pemasukan', 'pengeluaran' => 'Pengeluaran', 'labarugi' => 'Laba/Rugi',
    'piutang' => 'Piutang', 'hutang' => 'Hutang', 'stok' => 'Stok',
    'bulanan' => 'Rekap Bulanan', 'tahunan' => 'Rekap Tahunan',
];
$params = ['tab' => $tab];
if (!($no_periode ?? false)) {
    $params['dari'] = $dari;
    $params['sampai'] = $sampai;
}
if ($tab === 'bulanan' && isset($tahun)) {
    $params['tahun'] = $tahun;
}
$filterParams = array_diff_key($params, ['tab' => '']);
?>
<div class="mb-4 flex gap-2 overflow-x-auto pb-1 no-print">
    <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= url('laporan', ['tab' => $key]) ?>" class="btn whitespace-nowrap <?= $tab === $key ? 'btn-primary' : 'btn-secondary' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <div class="flex flex-col md:flex-row gap-3 items-end justify-between">
        <form method="get" action="<?= url('laporan') ?>" class="js-filter-form flex flex-col sm:flex-row gap-2 items-end">
            <input type="hidden" name="page" value="laporan">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <?php if (empty($no_periode)): ?>
                <div>
                    <label class="label">Dari</label>
                    <input type="date" name="dari" class="input" value="<?= e($dari) ?>" required max="9999-12-31">
                </div>
                <div>
                    <label class="label">Sampai</label>
                    <input type="date" name="sampai" class="input" value="<?= e($sampai) ?>" required max="9999-12-31">
                </div>
            <?php endif; ?>
            <?php if ($tab === 'bulanan'): ?>
                <div>
                    <label class="label">Tahun</label>
                    <select name="tahun" class="input">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>
        </form>
        <div class="flex gap-2">
            <a href="<?= url('laporan', array_merge(['action' => 'print'], $params)) ?>" target="_blank" class="btn btn-secondary"><?= icon('printer', 'w-4 h-4') ?> Cetak / PDF</a>
            <a href="<?= url('laporan', array_merge(['action' => 'export'], $params)) ?>" class="btn btn-secondary"><?= icon('download', 'w-4 h-4') ?> Export Excel</a>
        </div>
    </div>
</div>

<div class="print-area bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="text-center mb-4 border-b border-slate-200 pb-4">
        <h2 class="text-lg font-bold text-slate-800"><?= e($title) ?></h2>
        <?php if (!empty($subtitle)): ?><p class="text-sm text-slate-500"><?= e($subtitle) ?></p><?php endif; ?>
        <?php if (empty($no_periode)): ?>
            <p class="text-sm text-slate-500">Periode: <?= tanggal($dari) ?> s.d. <?= tanggal($sampai) ?></p>
        <?php elseif ($tab === 'bulanan'): ?>
            <p class="text-sm text-slate-500">Tahun: <?= e($tahun) ?></p>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><?php foreach ($columns as $label): ?><th><?= e($label) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($columns) ?>" class="text-center text-slate-400 py-8">Tidak ada data pada periode ini.</td></tr>
            <?php else: foreach ($rows as $row): ?>
                <tr>
                    <?php foreach (array_keys($columns) as $key): ?>
                        <?php
                        $val = $row[$key] ?? '';
                        if (in_array($key, $moneyCols, true)) {
                            $val = '<span class="' . ($tab === 'labarugi' && ($row['tag'] ?? '') === 'biaya' ? 'text-red-600' : '') . '">' . rupiah((float)$val) . '</span>';
                        } elseif ($key === 'jenis' && isset($jenisLabel)) {
                            $val = e($jenisLabel[$val] ?? $val);
                        } else {
                            $val = e($val);
                        }
                        ?>
                        <td class="<?= in_array($key, $moneyCols, true) ? 'text-right whitespace-nowrap' : 'whitespace-nowrap' ?> <?= ($tab === 'labarugi' && ($row['tag'] ?? '') === 'bersih') ? 'font-bold border-t-2 border-slate-300' : (($tab === 'labarugi' && ($row['tag'] ?? '') === 'kotor') ? 'font-semibold' : '') ?>"><?= $val ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($tab === 'kas'): ?>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
            <div class="bg-slate-50 rounded-lg p-3 text-center"><div class="text-xs text-slate-500">Saldo Awal</div><div class="font-bold"><?= rupiah($totals['kas_awal']) ?></div></div>
            <div class="bg-emerald-50 rounded-lg p-3 text-center"><div class="text-xs text-slate-500">Kas Masuk</div><div class="font-bold text-emerald-600"><?= rupiah($totals['kas_masuk']) ?></div></div>
            <div class="bg-red-50 rounded-lg p-3 text-center"><div class="text-xs text-slate-500">Kas Keluar</div><div class="font-bold text-red-600"><?= rupiah($totals['kas_keluar']) ?></div></div>
            <div class="bg-emerald-100 rounded-lg p-3 text-center"><div class="text-xs text-slate-500">Saldo Akhir</div><div class="font-bold text-emerald-800"><?= rupiah($totals['kas_akhir']) ?></div></div>
        </div>
    <?php elseif ($tab === 'penjualan' || $tab === 'pembelian'): ?>
        <div class="grid grid-cols-3 gap-3 mt-4">
            <div class="bg-slate-50 rounded-lg p-3 text-center"><div class="text-xs text-slate-500">Total Tunai</div><div class="font-bold"><?= rupiah($totals['tunai']) ?></div></div>
            <div class="bg-slate-50 rounded-lg p-3 text-center"><div class="text-xs text-slate-500">Total Kredit</div><div class="font-bold"><?= rupiah($totals['kredit']) ?></div></div>
            <div class="bg-emerald-100 rounded-lg p-3 text-center"><div class="text-xs text-slate-500">Grand Total</div><div class="font-bold text-emerald-800"><?= rupiah($totals['total']) ?></div></div>
        </div>
    <?php elseif ($tab === 'pemasukan' || $tab === 'pengeluaran'): ?>
        <div class="mt-4 flex flex-wrap gap-2">
            <span class="text-xs font-semibold text-slate-500 py-1">Total: <?= rupiah($totals['total']) ?></span>
            <?php foreach ($totals['per_kategori'] ?? [] as $k => $v): ?>
                <span class="text-[11px] px-2 py-1 rounded-full bg-slate-100 text-slate-700"><?= e($k) ?>: <?= rupiah($v) ?></span>
            <?php endforeach; ?>
        </div>
    <?php elseif ($tab === 'labarugi'): ?>
        <div class="mt-4 flex justify-end">
            <div class="px-4 py-2 rounded-lg <?= $totals['laba_rugi'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' ?> font-bold">
                Laba/Rugi Bersih: <?= rupiah($totals['laba_rugi']) ?>
            </div>
        </div>
    <?php elseif ($tab === 'piutang' || $tab === 'hutang'): ?>
        <div class="mt-4 flex justify-end">
            <div class="px-4 py-2 rounded-lg bg-fuchsia-50 text-fuchsia-700 font-bold">Total Sisa <?= $tab === 'piutang' ? 'Piutang' : 'Hutang' ?>: <?= rupiah($totals['sisa']) ?></div>
        </div>
    <?php elseif ($tab === 'stok'): ?>
        <div class="mt-4 flex justify-end">
            <div class="px-4 py-2 rounded-lg bg-emerald-50 text-emerald-800 font-bold">Total Nilai Stok: <?= rupiah($totals['nilai']) ?></div>
        </div>
    <?php endif; ?>
</div>