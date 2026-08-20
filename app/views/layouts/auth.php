<?php
/** Layout khusus autentikasi (login & ganti password) */
$profile = $profile ?? koperasi_profile();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Masuk') ?> - <?= e($profile['nama_koperasi'] ?? APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
<script src="<?= asset('assets/js/tailwind.min.js') ?>"></script>
<link rel="stylesheet" href="<?= asset('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="bg-slate-100 min-h-screen flex flex-col">
<main class="flex-1 flex items-center justify-center p-4">
    <?= flash_swal_scripts() ?>
    <?= $content ?>
</main>
<script src="<?= asset('assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
