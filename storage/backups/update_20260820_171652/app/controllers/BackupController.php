<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/BackupService.php';

class BackupController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator']);
        $svc = new BackupService();
        $backups = $svc->listBackups();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                flash('error', 'Token keamanan tidak valid.');
                redirect('backup');
            }
            $action = input('action', '');

            if ($action === 'create') {
                try {
                    $name = $svc->createBackup();
                    audit_log('BACKUP DATABASE', $name);
                    flash('success', 'Backup berhasil: ' . $name);
                } catch (Throwable $e) {
                    flash('error', 'Backup gagal: ' . $e->getMessage());
                }
            } elseif ($action === 'restore_upload') {
                try {
                    if (empty($_FILES['file']['tmp_name'])) {
                        throw new RuntimeException('Pilih file backup terlebih dahulu.');
                    }
                    $svc->restoreUpload($_FILES['file']['tmp_name']);
                    audit_log('RESTORE DATABASE', 'upload: ' . $_FILES['file']['name']);
                    flash('success', 'Restore selesai. Data diganti dari file backup.');
                } catch (Throwable $e) {
                    flash('error', 'Restore gagal: ' . $e->getMessage());
                }
            }
            redirect('backup');
        }

        $this->render('backup/index', [
            'pageTitle' => 'Backup / Restore Database',
            'backups' => $backups,
        ]);
    }

    public function download(?string $id = null): void
    {
        $this->guard(['Administrator']);
        (new BackupService())->download(input('file', ''));
    }

    public function delete(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('backup');
        }
        $filename = input('file', '');
        $path = BACKUP_DIR . DIRECTORY_SEPARATOR . basename($filename);

        if (!preg_match('/^backup_.*\.sql$/', $filename) || !file_exists($path)) {
            flash('error', 'File backup tidak ditemukan.');
            redirect('backup');
        }
        if (unlink($path)) {
            audit_log('HAPUS BACKUP', $filename);
            flash('success', 'File backup dihapus: ' . $filename);
        } else {
            flash('error', 'Gagal menghapus file backup.');
        }
        redirect('backup');
    }

    public function restore(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('backup');
        }
        $filename = input('file', '');
        try {
            (new BackupService())->restoreFile($filename);
            audit_log('RESTORE DATABASE', $filename);
            flash('success', 'Restore selesai dari: ' . $filename);
        } catch (Throwable $e) {
            flash('error', 'Restore gagal: ' . $e->getMessage());
        }
        redirect('backup');
    }
}