<?php
/** Tampilan cetak Riwayat Transaksi (CSS print). */
$p = $profile;
$user = current_user();
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
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 20px; }
    .header .nama { font-size: 18px; font-weight: bold; }
    .judul { text-align: center; margin-bottom: 4px; }
    .judul h2 { margin: 0; font-size: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #94a3b8; padding: 6px 8px; text-align: left; }
    th { background: #e2e8f0; }
    .num { text-align: right; white-space: nowrap; }
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
        <div class="nama"><?= e($p['nama_koperasi']) ?></div>
        <div><?= e($p['nama_sekolah']) ?></div>
        <div><?= e($p['alamat']) ?></div>
    </div>
    <div class="judul">
        <h2><?= e($title) ?></h2>
        <div>Periode: <?= tanggal($dari) ?> s.d. <?= tanggal($sampai) ?></div>
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
                    $class = in_array($key, $moneyCols, true) ? 'num' : '';
                    if (in_array($key, $moneyCols, true)) {
                        $val = rupiah((float)$val);
                    }
                    ?>
                    <td class="<?= $class ?>"><?= e($val) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="tandatangan">
            <div>Mengetahui,<br><?= e($p['nama_koperasi']) ?></div>
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