<?php
list($statusLabel, $statusCls) = status_tagihan((float)$piutang['total'] - (float)$sisa, (float)$sisa);
$fin = new FinanceService();
$dibayar = (float)$piutang['total'] - (float)$sisa;
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-slate-800 text-lg"><?= e($piutang['pelanggan']) ?></h2>
                    <p class="text-sm text-slate-500"><?= e($piutang['no_transaksi']) ?> &middot; <?= tanggal($piutang['tanggal']) ?></p>
                </div>
                <span class="text-[11px] px-2 py-0.5 rounded-full font-medium <?= $statusCls ?>"><?= e($statusLabel) ?></span>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center mb-4">
                <div class="bg-slate-50 rounded-lg p-3">
                    <div class="text-xs text-slate-500">Total Piutang</div>
                    <div class="text-lg font-bold"><?= rupiah($piutang['total']) ?></div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-3">
                    <div class="text-xs text-slate-500">Sudah Dibayar</div>
                    <div class="text-lg font-bold text-emerald-600"><?= rupiah($dibayar) ?></div>
                </div>
                <div class="bg-fuchsia-50 rounded-lg p-3">
                    <div class="text-xs text-slate-500">Sisa</div>
                    <div class="text-lg font-bold text-fuchsia-600"><?= rupiah($sisa) ?></div>
                </div>
            </div>

            <?php if ($piutang['status'] === 'DIBATALKAN'): ?>
                <div class="mb-4 text-xs text-red-600">Piutang ini telah dibatalkan (penjualan dibatalkan).</div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-3">Riwayat Pembayaran</h3>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tanggal</th><th>No Bukti</th><th class="text-right">Nominal</th><th>Keterangan</th><th>User</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="6" class="text-center text-slate-400 py-6">Belum ada pembayaran.</td></tr>
                    <?php else: foreach ($payments as $p): ?>
                        <tr class="<?= $p['status'] === 'DIBATALKAN' ? 'opacity-50' : '' ?>">
                            <td class="whitespace-nowrap"><?= tanggal($p['tanggal']) ?></td>
                            <td class="font-mono text-xs"><?= e($p['no_bukti']) ?></td>
                            <td class="text-right font-semibold text-emerald-600"><?= rupiah($p['nominal']) ?></td>
                            <td><?= e($p['keterangan'] ?: '-') ?></td>
                            <td class="text-xs text-slate-500"><?= e($p['username'] ?? '-') ?></td>
                            <td>
                                <?php if (has_role('Administrator') && $p['status'] === 'AKTIF'): ?>
                                    <form method="post" action="<?= url('piutang', ['action' => 'cancel_bayar', 'id' => $p['id']]) ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost p-1.5" onclick="return appConfirmSubmit(event, 'Batalkan pembayaran ini? Kas akan dibalik.')" title="Batalkan"><?= icon('x', 'w-4 h-4') ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <?php if ($sisa > 0 && $piutang['status'] === 'AKTIF') : ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-3">Catat Pembayaran</h3>
            <form method="post" action="<?= url('piutang', ['action' => 'bayar', 'id' => $piutang['id']]) ?>" class="space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label class="label">Tanggal</label>
                    <input type="date" name="tanggal" class="input" value="<?= date('Y-m-d') ?>" required max="9999-12-31">
                </div>
                <div>
                    <label class="label">Nominal * (sisa <?= rupiah($sisa) ?>)</label>
                    <input type="text" name="nominal" class="input" inputmode="numeric" min="1" max="<?= e((float)$sisa) ?>" step="0.01" required>
                </div>
                <div>
                    <label class="label">Keterangan</label>
                    <input type="text" name="keterangan" class="input" placeholder="Opsional...">
                </div>
                <button type="submit" class="btn btn-primary w-full"><?= icon('cash', 'w-4 h-4') ?> Bayar Piutang</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <a href="<?= url('piutang') ?>" class="btn btn-secondary w-full">Kembali</a>
        </div>
    </div>
</div>