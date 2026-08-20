<?php $isBatal = $tx['status'] === 'DIBATALKAN'; ?>
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="font-semibold text-slate-800 text-lg">Ubah Pembelian</h2>
                <p class="text-sm text-slate-500"><?= e($tx['no_transaksi']) ?> — hanya tanggal & keterangan (item/stok/kas tidak diubah)</p>
            </div>
            <a href="<?= url('pembelian', ['action' => 'show', 'id' => $tx['id']]) ?>" class="btn btn-ghost"><?= icon('chevron-left', 'w-4 h-4') ?> Kembali</a>
        </div>

        <?php if ($isBatal): ?>
            <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-red-700 text-sm">Transaksi ini sudah dibatalkan, tidak dapat diubah.</div>
        <?php endif; ?>

        <form method="post" action="<?= url('pembelian', ['action' => 'update', 'id' => $tx['id']]) ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <div>
                <label class="label">Tanggal *</label>
                <input type="date" name="tanggal" class="input" value="<?= e($tx['tanggal']) ?>" required max="9999-12-31" <?= $isBatal ? 'disabled' : '' ?>>
            </div>
            <div>
                <label class="label">Supplier</label>
                <input type="text" class="input" value="<?= e($tx['supplier'] ?? '-') ?>" disabled>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Keterangan</label>
                <input type="text" name="keterangan" class="input" value="<?= e($tx['keterangan']) ?>" <?= $isBatal ? 'disabled' : '' ?>>
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
                <a href="<?= url('pembelian', ['action' => 'show', 'id' => $tx['id']]) ?>" class="btn btn-secondary">Batal</a>
                <?php if (!$isBatal): ?>
                    <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>