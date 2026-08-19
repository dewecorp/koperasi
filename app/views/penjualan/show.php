<?php
$isBatal = $tx['status'] === 'DIBATALKAN';
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-slate-800 text-lg"><?= e($tx['no_transaksi']) ?></h2>
                    <p class="text-sm text-slate-500"><?= tanggal($tx['tanggal']) ?> &middot; oleh <?= e($tx['user_name']) ?></p>
                </div>
                <div class="flex gap-2">
                    <a href="<?= url('penjualan', ['action' => 'struk', 'id' => $tx['id']]) ?>" target="_blank" class="btn btn-secondary"><?= icon('printer', 'w-4 h-4') ?> Cetak Struk</a>
                    <a href="<?= url('penjualan') ?>" class="btn btn-ghost">Kembali</a>
                </div>
            </div>

            <?php if ($isBatal): ?>
                <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-red-700 text-sm">
                    <b>Transaksi dibatalkan</b> pada <?= tanggal_waktu($tx['cancelled_at']) ?>.<br>Alasan: <?= e($tx['alasan_batal']) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm mb-4">
                <div><span class="text-slate-500 block text-xs">Pelanggan</span><b><?= e($tx['pelanggan'] ?? 'Umum') ?></b></div>
                <div><span class="text-slate-500 block text-xs">Metode</span><b><?= e($tx['payment_method']) ?></b></div>
                <div><span class="text-slate-500 block text-xs">Keterangan</span><b><?= e($tx['keterangan'] ?: '-') ?></b></div>
                <div><span class="text-slate-500 block text-xs">Status</span><b><?= $isBatal ? 'Dibatalkan' : 'Aktif' ?></b></div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Kode</th><th>Barang</th><th class="text-right">Jumlah</th><th class="text-right">Harga</th><th class="text-right">Diskon</th><th class="text-right">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($details as $d): ?>
                        <tr>
                            <td class="font-mono text-xs"><?= e($d['kode']) ?></td>
                            <td><?= e($d['nama_barang']) ?></td>
                            <td class="text-right"><?= angka($d['qty']) ?> <?= e($d['satuan']) ?></td>
                            <td class="text-right"><?= rupiah($d['harga']) ?></td>
                            <td class="text-right"><?= rupiah($d['diskon']) ?></td>
                            <td class="text-right font-semibold"><?= rupiah($d['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-200">
                            <td colspan="5" class="text-right font-semibold">Total</td>
                            <td class="text-right font-bold text-emerald-600"><?= rupiah($tx['total']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-3">Bukti Transaksi</h3>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <?= attachment_badges($attachments) ?>
                    <?php if (has_role('Administrator') || has_role('Bendahara')): foreach ($attachments as $att): ?>
                        <form method="post" action="<?= url('penjualan', ['action' => 'delete_att', 'id' => $att['id'], 'tx' => $tx['id']]) ?>" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-[11px] text-red-600 hover:text-red-800 underline ml-1" onclick="return appConfirmSubmit(event, 'Hapus bukti ini?')">hapus</button>
                        </form>
                    <?php endforeach; endif; ?>
                </div>
                <?php if (!$isBatal && (has_role('Administrator') || has_role('Bendahara') || has_role('Petugas'))): ?>
                    <form method="post" action="<?= url('penjualan', ['action' => 'upload', 'id' => $tx['id']]) ?>" enctype="multipart/form-data" class="flex items-center gap-2">
                        <?= csrf_field() ?>
                        <input type="file" name="bukti" class="input text-sm !w-56" accept=".jpg,.jpeg,.png,.pdf" required>
                        <button type="submit" class="btn btn-secondary"><?= icon('upload', 'w-4 h-4') ?> Upload</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-2">Riwayat Kas & Piutang</h3>
            <?php
            $fin = new FinanceService();
            $pdo = db();
            $stmt = $pdo->prepare('SELECT * FROM cash_transactions WHERE related_type = "transactions" AND related_id = ? AND status = "AKTIF"');
            $stmt->execute([$tx['id']]);
            $cash = $stmt->fetchAll();
            $stmt = $pdo->prepare('SELECT * FROM receivables WHERE transaction_id = ? AND status = "AKTIF"');
            $stmt->execute([$tx['id']]);
            $piutang = $stmt->fetchAll();
            ?>
            <div class="space-y-2 text-sm">
                <?php if (empty($cash) && empty($piutang)): ?>
                    <p class="text-slate-400">Tidak ada.</p>
                <?php endif; ?>
                <?php foreach ($cash as $c): ?>
                    <div class="flex justify-between border border-slate-100 rounded-lg px-3 py-2">
                        <span class="text-slate-500">Kas <?= e($c['jenis']) ?> (<?= e($c['kategori']) ?>)</span>
                        <b class="<?= $c['jenis'] === 'masuk' ? 'text-emerald-600' : 'text-red-600' ?>"><?= rupiah($c['nominal']) ?></b>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($piutang as $r): ?>
                    <div class="flex justify-between border border-fuchsia-100 rounded-lg px-3 py-2">
                        <span class="text-slate-500">Piutang terdaftar</span>
                        <b class="text-fuchsia-600"><?= rupiah($r['total']) ?></b>
                    </div>
                    <?php
                    $stmt = $pdo->prepare('SELECT * FROM receivable_payments WHERE receivable_id = ? AND status = "AKTIF"');
                    $stmt->execute([$r['id']]);
                    foreach ($stmt->fetchAll() as $pm): ?>
                        <div class="flex justify-between border border-slate-100 rounded-lg px-3 py-2">
                            <span class="text-slate-500 text-xs">Bayar piutang <?= e($pm['no_bukti']) ?></span>
                            <b class="text-emerald-600"><?= rupiah($pm['nominal']) ?></b>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (has_role('Administrator') && !$isBatal): ?>
            <div class="bg-red-50 rounded-xl border border-red-200 p-5">
                <h3 class="font-semibold text-red-700 mb-2">Pembatalan Transaksi</h3>
                <p class="text-xs text-red-600 mb-3">Membatalkan akan mengembalikan stok dan membalik kas/piutang secara otomatis. Riwayat tetap tersimpan.</p>
                <form method="post" action="<?= url('penjualan', ['action' => 'cancel', 'id' => $tx['id']]) ?>">
                    <?= csrf_field() ?>
                    <textarea name="alasan" class="input mb-2" rows="2" placeholder="Alasan pembatalan (wajib)..." required></textarea>
                    <button type="submit" class="btn btn-danger w-full" onclick="return appConfirmSubmit(event, 'Batalkan transaksi ini? Efek kas/stok/piutang akan dibalik.')"><?= icon('x', 'w-4 h-4') ?> Batalkan Transaksi</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>