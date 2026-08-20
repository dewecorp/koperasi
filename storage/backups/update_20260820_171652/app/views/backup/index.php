<?php $p = koperasi_profile(); ?>
<div class="max-w-4xl">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-800 mb-1">Buat Backup Database</h2>
            <p class="text-sm text-slate-500 mb-4">Menghasilkan file SQL berisi seluruh data. File disimpan di folder storage/backups (tidak dapat diakses langsung via URL).</p>
            <form method="post" action="<?= url('backup') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <button type="submit" class="btn btn-primary"><?= icon('database', 'w-4 h-4') ?> Backup Sekarang</button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-800 mb-1">Restore dari File</h2>
            <p class="text-sm text-slate-500 mb-4">Unggah file SQL backup (.sql). Seluruh data akan diganti.</p>
            <form method="post" action="<?= url('backup') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="restore_upload">
                <input type="file" name="file" class="input mb-3" accept=".sql" required>
                <button type="submit" class="btn btn-danger" onclick="return appConfirmSubmit(event, 'PERINGATAN: Restore akan MENGGANTI seluruh data saat ini dengan isi file backup. Tindakan ini tidak dapat dibatalkan. Lanjutkan?')"><?= icon('refresh', 'w-4 h-4') ?> Restore dari File</button>
            </form>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-3">Daftar Backup Tersimpan</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama File</th><th class="text-right">Ukuran</th><th>Dibuat</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($backups)): ?>
                    <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada backup.</td></tr>
                <?php else: foreach ($backups as $b): ?>
                    <tr>
                        <td class="font-mono text-xs"><?= e($b['name']) ?></td>
                        <td class="text-right"><?= format_bytes($b['size']) ?></td>
                        <td><?= e($b['modified']) ?></td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="<?= url('backup', ['action' => 'download', 'file' => $b['name']]) ?>" class="btn btn-ghost p-1.5" title="Download"><?= icon('download', 'w-4 h-4') ?></a>
                                <form method="post" action="<?= url('backup', ['action' => 'restore']) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="file" value="<?= e($b['name']) ?>">
                                    <button type="submit" class="btn btn-ghost p-1.5" onclick="return appConfirmSubmit(event, 'PERINGATAN: Restore akan MENGGANTI seluruh data saat ini dengan isi file backup ini. Lanjutkan?')" title="Restore"><?= icon('refresh', 'w-4 h-4') ?></button>
                                </form>
                                <form method="post" action="<?= url('backup', ['action' => 'delete']) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="file" value="<?= e($b['name']) ?>">
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" onclick="return appConfirmSubmit(event, 'Hapus file backup ini?', 'Hapus Backup')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>