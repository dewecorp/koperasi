<?php

/**
 * Entry alternatif bila docroot diarahkan ke root project
 * (fallback jika mod_rewrite nonaktif). Arahkan ke front controller di public/.
 */

$publicIndex = __DIR__ . '/public/index.php';
if (!file_exists($publicIndex)) {
    http_response_code(500);
    die('Folder public/ tidak ditemukan.');
}
define('ROOT_ENTRY', true);
require $publicIndex;
