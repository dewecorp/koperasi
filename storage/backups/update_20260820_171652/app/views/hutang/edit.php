<?php ?>
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="font-semibold text-slate-800 text-lg">Ubah Hutang</h2>
                <p class="text-sm text-slate-500"><?= e($hutang['no_transaksi']) ?> — <?= e($hutang['supplier']) ?></p>
            </div>
            <a href="<?= url('hutang', ['action' => 'show', 'id' => $hutang['id']]) ?>" class="btn btn-ghost"><?= icon('chevron-left', 'w-4 h-4') ?> Kembali</a>
        </div>

        <form method="post" action="<?= url('hutang', ['action' => 'update', 'id' => $hutang['id']]) ?>" class="grid grid-cols-1 gap-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Total Hutang</label>
                    <input type="text" class="input" value="<?= rupiah($hutang['total']) ?>" disabled>
                </div>
                <div>
                    <label class="label">Jatuh Tempo</label>
                    <input type="date" name="jatuh_tempo" class="input" value="<?= e($hutang['jatuh_tempo'] ?? '') ?>">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <a href="<?= url('hutang', ['action' => 'show', 'id' => $hutang['id']]) ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>