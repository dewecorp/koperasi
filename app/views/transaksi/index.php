<?php ?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('transaksi') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="transaksi">
            <input type="date" name="dari" value="<?= e($dari) ?>" class="input w-full md:w-40">
            <input type="date" name="sampai" value="<?= e($sampai) ?>" class="input w-full md:w-40">
            <select name="jenis" class="input w-full md:w-36">
                <option value="">Semua Jenis</option>
                <option value="penjualan" <?= $jenis === 'penjualan' ? 'selected' : '' ?>>Penjualan</option>
                <option value="pembelian" <?= $jenis === 'pembelian' ? 'selected' : '' ?>>Pembelian</option>
            </select>
            <div class="relative flex-1">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari nomor / rekanan / keterangan...">
            </div>
        </form>
        <div class="flex gap-2">
            <a href="<?= url('transaksi', ['action' => 'print', 'dari' => $dari, 'sampai' => $sampai, 'q' => $q, 'jenis' => $jenis]) ?>" target="_blank" class="btn btn-secondary whitespace-nowrap"><?= icon('printer', 'w-4 h-4') ?> Cetak / PDF</a>
            <a href="<?= url('transaksi', ['action' => 'export', 'dari' => $dari, 'sampai' => $sampai, 'q' => $q, 'jenis' => $jenis]) ?>" class="btn btn-secondary whitespace-nowrap"><?= icon('download', 'w-4 h-4') ?> Export Excel</a>
        </div>
    </div>

    <div class="mb-3 text-xs text-slate-400">Riwayat hanya menampilkan transaksi aktif. Data yang dibatalkan tidak tampil di sini.</div>

    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>Tanggal</th><th>No. Transaksi</th><th>Jenis</th><th>Pelanggan / Supplier</th><th>Metode</th><th>Keterangan</th><th class="text-right">Total</th><th>User</th>
            </tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="8" class="text-center text-slate-400 py-8">Tidak ada data.</td></tr>
            <?php else: foreach ($pg['items'] as $t): ?>
                <tr>
                    <td class="whitespace-nowrap"><?= tanggal($t['tanggal']) ?></td>
                    <td class="font-mono text-xs"><?= e($t['no_transaksi']) ?></td>
                    <td>
                        <span class="text-[11px] px-2 py-0.5 rounded-full <?= $t['jenis'] === 'Penjualan' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?> font-medium"><?= e($t['jenis']) ?></span>
                    </td>
                    <td><?= e($t['rekanan'] ?? '-') ?></td>
                    <td><span class="text-[11px] px-2 py-0.5 rounded-full <?= $t['payment_method'] === 'kredit' ? 'bg-fuchsia-100 text-fuchsia-700' : 'bg-sky-100 text-sky-700' ?> font-medium"><?= e($t['payment_method']) ?></span></td>
                    <td class="max-w-[200px] truncate"><?= e($t['keterangan']) ?></td>
                    <td class="text-right font-semibold whitespace-nowrap"><?= rupiah($t['total']) ?></td>
                    <td class="text-xs text-slate-500"><?= e($t['username']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_links($pg) ?>
</div>