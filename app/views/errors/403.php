<?php
$profile = koperasi_profile();
$message = $message ?? 'Anda tidak memiliki akses ke halaman ini.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 - <?= e($profile['nama_koperasi'] ?? APP_NAME) ?></title>
<script src="<?= asset('assets/js/tailwind.min.js') ?>"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
    <div class="text-6xl font-bold text-red-300">403</div>
    <h1 class="mt-4 text-xl font-semibold text-slate-800"><?= e($message) ?></h1>
    <a href="<?= url('dashboard') ?>" class="btn btn-primary mt-6">Kembali ke Dashboard</a>
</div>
</body>
</html>
