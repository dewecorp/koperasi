<?php
/** Dashboard */
$cards = [
    ['label' => 'Saldo Kas', 'value' => $saldoKas, 'icon' => 'cash', 'color' => 'bg-emerald-500'],
    ['label' => 'Pemasukan Bulan Ini', 'value' => $pemasukanBulan, 'icon' => 'trending-up', 'color' => 'bg-sky-500'],
    ['label' => 'Pengeluaran Bulan Ini', 'value' => $pengeluaranBulan, 'icon' => 'trending-down', 'color' => 'bg-rose-500'],
    ['label' => 'Penjualan Bulan Ini', 'value' => $penjualanBulan, 'icon' => 'cart', 'color' => 'bg-indigo-500'],
    ['label' => 'Pembelian Bulan Ini', 'value' => $pembelianBulan, 'icon' => 'package', 'color' => 'bg-amber-500'],
    ['label' => 'Piutang', 'value' => $piutangTotal, 'icon' => 'wallet', 'color' => 'bg-fuchsia-500'],
    ['label' => 'Hutang', 'value' => $hutangTotal, 'icon' => 'bank', 'color' => 'bg-orange-500'],
    ['label' => 'Modal Koperasi', 'value' => $modalTotal, 'icon' => 'safe', 'color' => 'bg-violet-500'],
    ['label' => 'Estimasi Laba/Rugi Bulan Ini', 'value' => $estimasiLaba, 'icon' => 'scale-balance', 'color' => $estimasiLaba >= 0 ? 'bg-teal-500' : 'bg-red-600'],
];
?>
<script>
window.chartBulanLabels = <?= $bulanLabels ?>;
window.chartSeriMasuk = <?= $seriMasuk ?>;
window.chartSeriKeluar = <?= $seriKeluar ?>;
window.chartHariLabels = <?= $hariLabels ?>;
window.chartSeriJual = <?= $seriJual ?>;
window.chartKatLabels = <?= $katLabels ?>;
window.chartKatNilai = <?= $katNilai ?>;
</script>
<div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ($cards as $c): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs font-medium text-slate-500"><?= e($c['label']) ?></div>
                <div class="mt-2 text-xl font-bold <?= $c['value'] < 0 ? 'text-red-600' : 'text-slate-800' ?>"><?= rupiah($c['value']) ?></div>
            </div>
            <div class="h-11 w-11 rounded-lg <?= $c['color'] ?> text-white flex items-center justify-center shrink-0">
                <?= icon($c['icon']) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Pemasukan vs Pengeluaran (12 Bulan)</h2>
        <div class="relative h-72"><canvas id="chartBulanan"></canvas></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Penjualan 7 Hari Terakhir</h2>
        <div class="relative h-72"><canvas id="chartHarian"></canvas></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Penjualan per Kategori</h2>
        <div class="relative h-72"><canvas id="chartKategori"></canvas></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Transaksi Terbaru</h2>
        <div class="table-wrap max-h-72 overflow-y-auto">
            <table>
                <thead><tr><th>Tanggal</th><th>No</th><th>Jenis</th><th>Keterangan</th><th class="text-right">Nominal</th><th>User</th></tr></thead>
                <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="6" class="text-center text-slate-400 py-6">Belum ada transaksi.</td></tr>
                <?php else: foreach ($recent as $r): ?>
                    <tr>
                        <td class="whitespace-nowrap"><?= tanggal($r['tanggal']) ?></td>
                        <td class="whitespace-nowrap font-mono text-xs"><?= e($r['no']) ?></td>
                        <td>
                            <?php
                            $badge = ['Penjualan' => 'bg-emerald-100 text-emerald-700', 'Pembelian' => 'bg-amber-100 text-amber-700', 'Pemasukan Lain' => 'bg-sky-100 text-sky-700', 'Pengeluaran' => 'bg-rose-100 text-rose-700', 'Bayar Piutang' => 'bg-fuchsia-100 text-fuchsia-700', 'Bayar Hutang' => 'bg-orange-100 text-orange-700'];
                            $cls = $badge[$r['jenis']] ?? 'bg-slate-100 text-slate-600';
                            ?>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium <?= $cls ?>"><?= e($r['jenis']) ?></span>
                        </td>
                        <td class="max-w-[160px] truncate"><?= e($r['keterangan']) ?></td>
                        <td class="text-right whitespace-nowrap font-medium"><?= rupiah($r['nominal']) ?></td>
                        <td class="whitespace-nowrap text-xs text-slate-500"><?= e($r['username']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
    <?php
    $pembayaranList = [
        ['judul' => 'Stok Hampir Habis', 'ikon' => 'box', 'warna' => 'text-red-600', 'rows' => $stokMenipis, 'kolom' => ['name' => 'Barang', 'stock' => 'Stok', 'stock_minimum' => 'Minimal'], 'link' => 'barang'],
        ['judul' => 'Piutang Jatuh Tempo', 'ikon' => 'wallet', 'warna' => 'text-amber-600', 'rows' => $piutangJatuhTempo, 'kolom' => ['pelanggan' => 'Pelanggan', 'jatuh_tempo' => 'Jatuh Tempo', 'total' => 'Total'], 'link' => 'piutang'],
        ['judul' => 'Hutang Jatuh Tempo', 'ikon' => 'bank', 'warna' => 'text-orange-600', 'rows' => $hutangJatuhTempo, 'kolom' => ['supplier' => 'Supplier', 'jatuh_tempo' => 'Jatuh Tempo', 'total' => 'Total'], 'link' => 'hutang'],
    ];
    ?>
    <?php foreach ($pembayaranList as $bl): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-800 mb-1 flex items-center gap-2 <?= e($bl['warna']) ?>"><?= icon($bl['ikon'], 'w-5 h-5') ?> <?= e($bl['judul']) ?></h3>
        <div class="table-wrap max-h-52 overflow-y-auto">
            <table>
                <thead><tr><?php foreach ($bl['kolom'] as $k => $label): ?><th><?= e($label) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php if (empty($bl['rows'])): ?>
                    <tr><td colspan="3" class="text-center text-slate-400 py-4">Tidak ada.</td></tr>
                <?php else: foreach ($bl['rows'] as $r): ?>
                    <tr>
                        <?php foreach ($bl['kolom'] as $k => $label): ?>
                            <td>
                                <?php if ($k === 'stock' || $k === 'stock_minimum'): ?>
                                    <?= angka($r[$k]) ?>
                                <?php elseif ($k === 'total'): ?>
                                    <?= rupiah($r[$k]) ?>
                                <?php elseif ($k === 'jatuh_tempo'): ?>
                                    <?= tanggal($r[$k]) ?>
                                <?php else: ?>
                                    <?= e($r[$k]) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <a href="<?= url($bl['link']) ?>" class="mt-2 inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">Lihat semua <?= icon('chevron-right', 'w-3 h-3') ?></a>
    </div>
    <?php endforeach; ?>
</div>