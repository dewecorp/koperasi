<?php /** Struk penjualan - tampilan cetak sederhana */
$p = $profile;
$nomor = $tx['no_transaksi'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Struk <?= e($nomor) ?></title>
<script src="<?= asset('assets/js/tailwind.min.js') ?>"></script>
<style>
  body { font-family: 'Courier New', monospace; }
  .struk { width: 80mm; margin: 0 auto; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="struk text-sm">
    <div class="text-center mb-2">
        <div class="font-bold text-base"><?= e($p['nama_koperasi']) ?></div>
        <div><?= e($p['nama_sekolah']) ?></div>
        <div><?= e($p['alamat']) ?></div>
        <div><?= e($p['telepon']) ?></div>
    </div>
    <div class="border-t border-b border-dashed border-slate-400 py-2 mb-2">
        <div class="flex justify-between"><span>No</span><span><?= e($nomor) ?></span></div>
        <div class="flex justify-between"><span>Tanggal</span><span><?= tanggal($tx['tanggal']) ?></span></div>
        <div class="flex justify-between"><span>Pelanggan</span><span><?= e($tx['pelanggan'] ?? 'Umum') ?></span></div>
        <div class="flex justify-between"><span>Kasir</span><span><?= e($tx['user_name']) ?></span></div>
        <div class="flex justify-between"><span>Metode</span><span><?= strtoupper(e($tx['payment_method'])) ?></span></div>
    </div>
    <table class="w-full mb-2">
        <thead><tr class="border-b border-dashed border-slate-400 text-left"><th>Barang</th><th class="text-center">Qty</th><th class="text-right">Harga</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        <?php foreach ($details as $d): ?>
            <tr>
                <td><?= e($d['nama_barang']) ?></td>
                <td class="text-center"><?= angka($d['qty']) ?></td>
                <td class="text-right"><?= number_format($d['harga'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($d['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php if ($d['diskon'] > 0): ?>
                <tr><td colspan="4" class="text-xs text-right">diskon: -<?= number_format($d['diskon'], 0, ',', '.') ?></td></tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="flex justify-between font-bold border-t border-dashed border-slate-400 pt-2">
        <span>TOTAL</span><span><?= number_format($tx['total'], 0, ',', '.') ?></span>
    </div>
    <?php if ($tx['keterangan']): ?><div class="mt-2 text-xs"><?= e($tx['keterangan']) ?></div><?php endif; ?>
    <div class="text-center mt-4 text-xs">Terima kasih atas kunjungan Anda</div>
    <div class="no-print text-center mt-4">
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded">Cetak</button>
        <button onclick="window.close()" class="px-4 py-2 bg-slate-200 rounded">Tutup</button>
    </div>
</div>
</body>
</html>
