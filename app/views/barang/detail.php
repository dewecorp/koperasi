<?php $canEdit = has_role('Administrator') || has_role('Bendahara'); ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h2 class="font-semibold text-slate-800 mb-3"><?= e($barang['name']) ?></h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-slate-500">Kode</dt><dd class="font-mono"><?= e($barang['kode']) ?></dd></div>
                <?php if ($barang['barcode']): ?><div class="flex justify-between"><dt class="text-slate-500">Barcode</dt><dd><?= e($barang['barcode']) ?></dd></div><?php endif; ?>
                <div class="flex justify-between"><dt class="text-slate-500">Kategori</dt><dd><?= e($barang['kategori'] ?? '-') ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Satuan</dt><dd><?= e($barang['satuan']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Supplier</dt><dd><?= e($barang['supplier_name'] ?? '-') ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Harga Beli</dt><dd><?= rupiah($barang['harga_beli']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Harga Jual</dt><dd><?= rupiah($barang['harga_jual']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Stok Saat Ini</dt><dd class="font-bold <?= (float)$barang['stock'] <= (float)$barang['stock_minimum'] ? 'text-red-600' : 'text-emerald-600' ?>"><?= angka($barang['stock']) ?> <?= e($barang['satuan']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Stok Minimum</dt><dd><?= angka($barang['stock_minimum']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd><?= (int)$barang['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></dd></div>
            </dl>
            <div class="flex gap-2 mt-4">
                <a href="<?= url('barang') ?>" class="btn btn-secondary"><?= icon('chevron-left', 'w-4 h-4') ?> Kembali</a>
                <?php if ($canEdit): ?>
                    <a href="<?= url('barang', ['action' => 'adjust', 'id' => $barang['id']]) ?>" class="btn btn-primary"><?= icon('sliders', 'w-4 h-4') ?> Penyesuaian Stok</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h2 class="font-semibold text-slate-800 mb-3">Kartu Stok</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tanggal</th><th>Referensi</th><th>Keterangan</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Saldo</th></tr></thead>
                    <tbody>
                    <?php if (empty($movements)): ?>
                        <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada mutasi stok.</td></tr>
                    <?php else: foreach ($movements as $m): ?>
                        <tr>
                            <td class="whitespace-nowrap"><?= tanggal($m['tanggal']) ?></td>
                            <td class="font-mono text-xs"><?= e($m['no_referensi'] ?? '-') ?></td>
                            <td><?= e($m['keterangan']) ?></td>
                            <td class="text-right text-emerald-600 font-medium"><?= $m['type'] === 'masuk' || ($m['type'] === 'penyesuaian' && $m['qty'] > 0) ? angka(abs($m['qty'])) : '-' ?></td>
                            <td class="text-right text-red-600 font-medium"><?= $m['type'] === 'keluar' || ($m['type'] === 'penyesuaian' && $m['qty'] < 0) ? angka(abs($m['qty'])) : '-' ?></td>
                            <td class="text-right font-bold"><?= angka($m['saldo']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
