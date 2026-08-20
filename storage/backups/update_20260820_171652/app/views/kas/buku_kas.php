<?php
$namaJenis = ['saldo_awal' => 'Saldo Awal', 'masuk' => 'Kas Masuk', 'keluar' => 'Kas Keluar'];
$warna = ['saldo_awal' => 'bg-slate-100 text-slate-600', 'masuk' => 'bg-emerald-100 text-emerald-700', 'keluar' => 'bg-red-100 text-red-700'];
$ranges = ['hari' => 'Hari Ini', 'minggu' => 'Minggu Ini', 'bulan' => 'Bulan Ini', 'tahun' => 'Tahun Ini', 'custom' => 'Custom'];
?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <form method="get" action="<?= url('kas') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 items-end">
        <input type="hidden" name="page" value="kas">
        <div>
            <label class="label">Periode</label>
            <select name="range" class="input">
                <?php foreach ($ranges as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $range === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
            <div>
                <label class="label">Dari</label>
                <input type="date" name="dari" class="input" data-range-date value="<?= e($dari) ?>">
            </div>
            <div>
                <label class="label">Sampai</label>
                <input type="date" name="sampai" class="input" data-range-date value="<?= e($sampai) ?>">
            </div>
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="text-xs text-slate-500">Saldo Awal</div>
        <div class="text-lg font-bold mt-1"><?= rupiah($buku['saldo_awal']) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="text-xs text-slate-500">Kas Masuk</div>
        <div class="text-lg font-bold mt-1 text-emerald-600">+ <?= rupiah($totMasuk) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="text-xs text-slate-500">Kas Keluar</div>
        <div class="text-lg font-bold mt-1 text-red-600">- <?= rupiah($totKeluar) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="text-xs text-slate-500">Saldo Akhir</div>
        <div class="text-lg font-bold mt-1 text-emerald-700"><?= rupiah($saldoAkhir) ?></div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tanggal</th><th>No Transaksi</th><th>Keterangan</th><th class="text-right">Kas Masuk</th><th class="text-right">Kas Keluar</th><th class="text-right">Saldo</th></tr></thead>
            <tbody>
            <?php if (empty($buku['rows'])): ?>
                <tr><td colspan="6" class="text-center text-slate-400 py-8">Tidak ada mutasi kas di periode ini.</td></tr>
            <?php else: foreach ($buku['rows'] as $r): ?>
                <tr>
                    <td class="whitespace-nowrap"><?= tanggal($r['tanggal']) ?></td>
                    <td class="font-mono text-xs"><?= e($r['no_transaksi']) ?></td>
                    <td>
                        <span class="text-[11px] px-2 py-0.5 rounded-full <?= $warna[$r['jenis']] ?? 'bg-slate-100' ?> font-medium mr-2"><?= e($namaJenis[$r['jenis']] ?? $r['jenis']) ?></span>
                        <?= e($r['keterangan']) ?>
                    </td>
                    <td class="text-right text-emerald-600 font-medium whitespace-nowrap"><?= $r['jenis'] === 'masuk' || $r['jenis'] === 'saldo_awal' ? rupiah($r['nominal']) : '-' ?></td>
                    <td class="text-right text-red-600 font-medium whitespace-nowrap"><?= $r['jenis'] === 'keluar' ? rupiah($r['nominal']) : '-' ?></td>
                    <td class="text-right font-bold whitespace-nowrap"><?= rupiah($r['saldo']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>