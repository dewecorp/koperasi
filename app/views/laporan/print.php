<?php
/** Tampilan cetak laporan (CSS print, tidak pakai layout). */
$p = $profile;
$user = current_user();
$isLaba = $tab === 'labarugi';
$isKas = $tab === 'kas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= e($title) ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 0; }
    .kertas { max-width: 800px; margin: 0 auto; padding: 24px; }
    .header { display: flex; align-items: center; gap: 16px; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 20px; }
    .header .logo-img { max-height: 70px; width: auto; object-fit: contain; }
    .header .teks { flex: 1; text-align: center; }
    .header .teks .nama { font-size: 18px; font-weight: bold; }
    .header .teks .alamat { font-size: 11px; color: #475569; }
    .judul { text-align: center; margin-bottom: 4px; }
    .judul h2 { margin: 0; font-size: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #94a3b8; padding: 6px 8px; text-align: left; }
    th { background: #e2e8f0; }
    .num { text-align: right; white-space: nowrap; }
    .total-row td { font-weight: bold; }
    .summary { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
    .summary .box { border: 1px solid #94a3b8; border-radius: 6px; padding: 8px 14px; text-align: center; }
    .summary .box b { display: block; font-size: 13px; margin-top: 2px; }
    .footer { margin-top: 40px; display: flex; justify-content: space-between; }
    .tandatangan { text-align: center; width: 220px; }
    .tandatangan .spasi { height: 70px; }
    .btn { position: fixed; top: 12px; right: 12px; padding: 8px 16px; background: #059669; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
    @media print { .btn { display: none; } .kertas { max-width: 100%; } }
</style>
</head>
<body>
<button class="btn" onclick="window.print()">Cetak</button>
<div class="kertas">
    <div class="header">
        <div style="flex-shrink:0;">
            <?php if (!empty($p['logo']) && file_exists(UPLOAD_DIR . '/' . $p['logo'])): ?>
                <img src="<?= asset('uploads/' . e($p['logo'])) ?>" class="logo-img" alt="logo">
            <?php endif; ?>
        </div>
        <div class="teks">
            <div class="nama"><?= e($p['nama_koperasi']) ?></div>
            <div><?= e($p['nama_sekolah']) ?></div>
            <div class="alamat"><?= e($p['alamat']) ?></div>
        </div>
    </div>
    <div class="judul">
        <h2><?= e($title) ?></h2>
        <?php if (!empty($subtitle)): ?><div><?= e($subtitle) ?></div><?php endif; ?>
        <?php if (empty($no_periode)): ?>
            <div>Periode: <?= tanggal($dari) ?> s.d. <?= tanggal($sampai) ?></div>
        <?php elseif ($tab === 'bulanan'): ?>
            <div>Tahun: <?= e($tahun) ?></div>
        <?php endif; ?>
    </div>

    <table>
        <thead><tr><?php foreach ($columns as $label): ?><th><?= e($label) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="<?= count($columns) ?>" style="text-align:center;color:#94a3b8;">Tidak ada data.</td></tr>
        <?php else: foreach ($rows as $row): ?>
            <tr>
                <?php foreach (array_keys($columns) as $key): ?>
                    <?php
                    $val = $row[$key] ?? '';
                    $class = '';
                    if (in_array($key, $moneyCols, true)) {
                        $val = rupiah((float)$val);
                        $class = 'num';
                    }
                    if (($row['tag'] ?? '') === 'bersih') {
                        $class .= ' total-row';
                    }
                    ?>
                    <td class="<?= $class ?>"><?= e($val) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php if ($isKas): ?>
        <div class="summary">
            <div class="box">Saldo Awal<br><b><?= rupiah($totals['kas_awal']) ?></b></div>
            <div class="box">Kas Masuk<br><b><?= rupiah($totals['kas_masuk']) ?></b></div>
            <div class="box">Kas Keluar<br><b><?= rupiah($totals['kas_keluar']) ?></b></div>
            <div class="box">Saldo Akhir<br><b><?= rupiah($totals['kas_akhir']) ?></b></div>
        </div>
    <?php elseif ($tab === 'labarugi'): ?>
        <div class="summary"><div class="box">Laba/Rugi Bersih<br><b><?= rupiah($totals['laba_rugi']) ?></b></div></div>
    <?php elseif (isset($totals['sisa'])): ?>
        <div class="summary"><div class="box">Total Sisa<br><b><?= rupiah($totals['sisa']) ?></b></div></div>
    <?php elseif (isset($totals['nilai'])): ?>
        <div class="summary"><div class="box">Total Nilai<br><b><?= rupiah($totals['nilai']) ?></b></div></div>
    <?php endif; ?>

    <div class="footer">
        <div class="tandatangan">
            <div>Mengetahui,<br>Kepala/<?= e($p['nama_koperasi']) ?></div>
            <div class="spasi"></div>
            <div>( <?= e($p['nama_ketua'] ?: '................') ?> )</div>
        </div>
        <div class="tandatangan">
            <div>Dicetak <?= tanggal(date('Y-m-d')) ?> oleh <?= e($user['name'] ?? '') ?></div>
            <div class="spasi"></div>
            <div>( <?= e($p['nama_bendahara'] ?: '................') ?> )</div>
        </div>
    </div>
</div>
</body>
</html>