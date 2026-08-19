<?php

define('APP_NAME', 'Koperasi Sekolah');
define('APP_VERSION', '1.0.0');
define('APP_ROOT', dirname(__DIR__));
define('APP_TIMEZONE', 'Asia/Jakarta');
define('APP_MAX_UPLOAD', 2 * 1024 * 1024); // 2 MB
define('APP_SESSION_NAME', 'KOPSEK_SESSION');

date_default_timezone_set(APP_TIMEZONE);

// Folder untuk backup database (di luar public agar tidak diakses langsung)
define('BACKUP_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups');
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads');
