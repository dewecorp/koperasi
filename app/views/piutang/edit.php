<?php ?>
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="font-semibold text-slate-800 text-lg">Ubah Piutang</h2>
                <p class="text-sm text-slate-500"><?= e($piutang['no_transaksi']) ?> — <?= e($piutang['pelanggan']) ?></p>
            </div>
            <a href="<?= url('piutang', ['action' => 'show', 'id' => $piutang['id']]) ?>" class="btn btn-ghost"><?= icon('chevron-left', 'w-4 h-4') ?> Kembali</a>
        </div>

        <form method="post" action="<?= url('piutang', ['action' => 'update', 'id' => $piutang['id']]) ?>" class="grid grid-cols-1 gap-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Total Piutang</label>
                    <input type="text" class="input" value="<?= rupiah($piutang['total']) ?>" disabled>
                </div>
                <div>
                    <label class="label">Jatuh Tempo</label>
                    <input type="date" name="jatuh_tempo" class="input" value="<?= e($piutang['jatuh_tempo'] ?? '') ?>">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <a href="<?= url('piutang', ['action' => 'show', 'id' => $piutang['id']]) ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>