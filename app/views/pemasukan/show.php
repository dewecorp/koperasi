<?php
$isBatal = $tx['status'] === 'DIBATALKAN';
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM cash_transactions WHERE related_type = "transactions" AND related_id = ? AND status = "AKTIF"');
$stmt->execute([$tx['id']]);
$cash = $stmt->fetchAll();
?>
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="font-semibold text-slate-800 text-lg"><?= e($tx['no_transaksi']) ?></h2>
                <p class="text-sm text-slate-500"><?= tanggal($tx['tanggal']) ?> &middot; oleh <?= e($tx['user_name']) ?></p>
            </div>
            <a href="<?= url('pemasukan') ?>" class="btn btn-ghost">Kembali</a>
        </div>
        <?php if ($isBatal): ?>
            <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-red-700 text-sm">
                <b>Dibatalkan</b> <?= tanggal_waktu($tx['cancelled_at']) ?> — <?= e($tx['alasan_batal']) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            <div><span class="text-slate-500 block text-xs">Kategori</span><b><?= e($tx['kategori'] ?? '-') ?></b></div>
            <div><span class="text-slate-500 block text-xs">Nominal</span><b class="text-emerald-600"><?= rupiah($tx['total']) ?></b></div>
            <div class="col-span-2"><span class="text-slate-500 block text-xs">Keterangan</span><b><?= e($tx['keterangan'] ?: '-') ?></b></div>
        </div>

        <div class="border border-slate-100 rounded-lg px-4 py-3 text-sm mb-4">
            <div class="flex justify-between">
                <span class="text-slate-500">Kas bertambah</span>
                <b class="text-emerald-600"><?= !empty($cash) ? rupiah($cash[0]['nominal']) : '-'; ?></b>
            </div>
        </div>

        <h3 class="font-semibold text-slate-800 mb-2">Bukti</h3>
        <div class="flex items-start justify-between gap-4 mb-4">
            <?= attachment_badges($attachments) ?>
            <?php if (!$isBatal): ?>
                <form method="post" action="<?= url('pemasukan', ['action' => 'upload', 'id' => $tx['id']]) ?>" enctype="multipart/form-data" class="flex items-center gap-2">
                    <?= csrf_field() ?>
                    <input type="file" name="bukti" class="input text-sm !w-56" accept=".jpg,.jpeg,.png,.pdf" required>
                    <button type="submit" class="btn btn-secondary"><?= icon('upload', 'w-4 h-4') ?> Upload</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (has_role('Administrator') && !$isBatal): ?>
            <div class="bg-red-50 rounded-xl border border-red-200 p-4 mt-2">
                <h3 class="font-semibold text-red-700 mb-2">Pembatalan</h3>
                <form method="post" action="<?= url('pemasukan', ['action' => 'cancel', 'id' => $tx['id']]) ?>">
                    <?= csrf_field() ?>
                    <div class="flex gap-2">
                        <input type="text" name="alasan" class="input" placeholder="Alasan pembatalan (wajib)" required>
                        <button type="submit" class="btn btn-danger whitespace-nowrap" onclick="return appConfirmSubmit(event, 'Batalkan pemasukan ini? Kas akan dibalik.')"><?= icon('x', 'w-4 h-4') ?> Batalkan</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>