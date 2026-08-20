<?php

/**
 * Backup & restore database tanpa dependensi eksternal (mysqldump-lite).
 * File backup disimpan di storage/backups (di luar public).
 */

class BackupService extends Model
{
    public function tables(): array
    {
        $rows = $this->all('SHOW TABLES');
        $out = [];
        foreach ($rows as $row) {
            $out[] = array_values($row)[0];
        }
        return $out;
    }

    public function createBackup(): string
    {
        if (!is_dir(BACKUP_DIR)) {
            mkdir(BACKUP_DIR, 0775, true);
        }
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $path = BACKUP_DIR . DIRECTORY_SEPARATOR . $filename;

        $sql = "-- Koperasi Sekolah - Backup Database\n";
        $sql .= "-- Tanggal: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- DB: " . DB_NAME . "\n\n";
        $sql .= "USE `" . DB_NAME . "`;\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($this->tables() as $table) {
            $stmt = $this->pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $row[1] . ";\n\n";

            $rows = $this->all("SELECT * FROM `$table`");
            foreach ($rows as $r) {
                $cols = array_keys($r);
                $colStr = '`' . implode('`,`', $cols) . '`';
                $vals = [];
                foreach ($r as $v) {
                    $vals[] = $v === null ? 'NULL' : $this->pdo->quote($v);
                }
                $sql .= "INSERT INTO `$table` ($colStr) VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($path, $sql);
        return $filename;
    }

    public function listBackups(): array
    {
        if (!is_dir(BACKUP_DIR)) {
            return [];
        }
        $files = glob(BACKUP_DIR . DIRECTORY_SEPARATOR . 'backup_*.sql');
        $out = [];
        foreach ($files as $f) {
            $out[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'modified' => date('Y-m-d H:i:s', filemtime($f)),
            ];
        }
        rsort($out);
        return $out;
    }

    public function restoreFile(string $filename): void
    {
        $path = BACKUP_DIR . DIRECTORY_SEPARATOR . basename($filename);
        if (!file_exists($path)) {
            throw new RuntimeException('File backup tidak ditemukan.');
        }
        $sql = file_get_contents($path);
        $this->restoreSql($sql);
    }

    public function restoreUpload(string $tmpPath): void
    {
        $sql = file_get_contents($tmpPath);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('File kosong atau tidak terbaca.');
        }
        $this->restoreSql($sql);
    }

    private function restoreSql(string $sql): void
    {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);
        if (!$pdo->exec($sql)) {
            throw new RuntimeException('Restore gagal.');
        }
    }

    public function download(string $filename): void
    {
        $path = BACKUP_DIR . DIRECTORY_SEPARATOR . basename($filename);
        if (!file_exists($path)) {
            abort_notfound('File backup tidak ditemukan.');
        }
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
