<?php

/**
 * Router untuk PHP built-in server (mode development).
 * Meniru perilaku clean URL .htaccess:
 *   php -S localhost:8000 -t public public/router.php
 *
 * Pada produksi (Apache/XAMPP/Laragon) cukup gunakan .htaccess di public/.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

// File statis disajikan langsung oleh server built-in
if ($ext !== '' && $ext !== 'php') {
    return false;
}

// Bersihkan ekstensi .php pada path (dev mode)
if (preg_match('#\.php$#', $path)) {
    $path = preg_replace('#\.php$#', '', $path);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
unset($_SERVER['PATH_INFO']);
$_SERVER['PATH_INFO'] = $path === '' || $path === '/' ? '/dashboard' : $path;

// Debug
error_log("router.php: REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? 'none') . " -> PATH_INFO=" . $_SERVER['PATH_INFO']);

require __DIR__ . '/index.php';