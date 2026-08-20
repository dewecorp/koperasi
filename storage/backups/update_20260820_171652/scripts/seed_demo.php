<?php

/**
 * Seeder data demo (transaksi contoh untuk uji dashboard & laporan).
 * Menjalankan: php scripts/seed_demo.php
 * Menghapus seluruh transaksi yang ada lalu mengisi data demo.
 */

$root = dirname(__DIR__);
require_once $root . '/app/helpers/functions.php';
require_once $root . '/app/helpers/auth.php';
require_once $root . '/app/services/FinanceService.php';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'name' => 'Administrator Koperasi', 'role_id' => 1, 'role_name' => 'Administrator'];

$pdo = db();
$fin = new FinanceService();

// ===== Reset seluruh data transaksi =====
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['receivable_payments','receivables','payable_payments','payables','cash_transactions','stock_movements','transaction_details','transactions','attachments','audit_logs','number_counters','capital_transactions'] as $t) {
    $pdo->exec("TRUNCATE TABLE $t");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
$pdo->exec('UPDATE products SET stock = 0');

echo "Menyiapkan stok awal...\n";
$stokAwal = [
    1 => 200, 2 => 300, 3 => 250, 4 => 150, 5 => 120, 6 => 80,
    7 => 25, 8 => 20, 9 => 500, 10 => 300,
];
foreach ($stokAwal as $pid => $qty) {
    $pdo->prepare('INSERT INTO stock_movements (product_id, tanggal, type, qty, keterangan, status, user_id) VALUES (?, CURDATE(), "penyesuaian", ?, "Stok awal (demo)", "AKTIF", 1)')->execute([$pid, $qty]);
    $fin->recalcStok($pid);
}

// Helper tanggal: n bulan lalu, tanggal d
$tgl = function (int $bulanLalu, int $hari) {
    return date('Y-m-d', mktime(0, 0, 0, (int)date('m') - $bulanLalu, $hari, (int)date('Y')));
};

// ===== Saldo awal =====
// Tanggal dibuat MENDULU sebelum seluruh transaksi demo agar buku kas
// membaca berurutan dari saldo awal (konsisten & mudah dipahami).
$fin->setSaldoAwal($tgl(5, 1), 5000000, 'Saldo awal koperasi');

echo "Membuat transaksi demo...\n";

// ---------- Pembelian (stok masuk) ----------
$fin->savePembelian($tgl(4, 3), 1, 'tunai', [
    ['product_id' => 1, 'qty' => 100, 'harga' => 2500, 'diskon' => 0, 'subtotal' => 250000],
    ['product_id' => 2, 'qty' => 150, 'harga' => 1500, 'diskon' => 0, 'subtotal' => 225000],
    ['product_id' => 3, 'qty' => 120, 'harga' => 1800, 'diskon' => 0, 'subtotal' => 216000],
    ['product_id' => 9, 'qty' => 300, 'harga' => 2000, 'diskon' => 0, 'subtotal' => 600000],
], 'Pembelian stok awal');
$fin->savePembelian($tgl(3, 5), 2, 'kredit', [
    ['product_id' => 4, 'qty' => 100, 'harga' => 1000, 'diskon' => 0, 'subtotal' => 100000],
    ['product_id' => 5, 'qty' => 80,  'harga' => 2000, 'diskon' => 0, 'subtotal' => 160000],
], 'Pembelian alat tulis kredit');
$fin->savePembelian($tgl(2, 10), 3, 'tunai', [
    ['product_id' => 7, 'qty' => 15, 'harga' => 95000, 'diskon' => 0, 'subtotal' => 1425000],
    ['product_id' => 8, 'qty' => 10, 'harga' => 20000, 'diskon' => 0, 'subtotal' => 200000],
], 'Stok seragam');
$fin->savePembelian($tgl(1, 8), 4, 'tunai', [
    ['product_id' => 9, 'qty' => 200, 'harga' => 2000, 'diskon' => 0, 'subtotal' => 400000],
    ['product_id' => 10, 'qty' => 200, 'harga' => 2500, 'diskon' => 0, 'subtotal' => 500000],
], 'Stok makanan & minuman');
$fin->savePembelian($tgl(0, 2), 1, 'tunai', [
    ['product_id' => 1, 'qty' => 50, 'harga' => 2500, 'diskon' => 0, 'subtotal' => 125000],
    ['product_id' => 6, 'qty' => 40, 'harga' => 4000, 'diskon' => 0, 'subtotal' => 160000],
], 'Restock');

// ---------- Penjualan ----------
$fin->savePenjualan($tgl(4, 8), 1, 'tunai', [
    ['product_id' => 1, 'qty' => 40, 'harga' => 3500, 'diskon' => 0, 'subtotal' => 140000],
    ['product_id' => 2, 'qty' => 50, 'harga' => 2500, 'diskon' => 0, 'subtotal' => 125000],
    ['product_id' => 9, 'qty' => 80, 'harga' => 3000, 'diskon' => 0, 'subtotal' => 240000],
], 'Penjualan harian');
$fin->savePenjualan($tgl(3, 12), 4, 'kredit', [
    ['product_id' => 7, 'qty' => 2, 'harga' => 110000, 'diskon' => 0, 'subtotal' => 220000],
    ['product_id' => 8, 'qty' => 1, 'harga' => 28000, 'diskon' => 0, 'subtotal' => 28000],
], 'Penjualan seragam kredit');
$fin->savePenjualan($tgl(2, 15), 2, 'tunai', [
    ['product_id' => 3, 'qty' => 60, 'harga' => 3000, 'diskon' => 0, 'subtotal' => 180000],
    ['product_id' => 10, 'qty' => 50, 'harga' => 4000, 'diskon' => 0, 'subtotal' => 200000],
], 'Penjualan');
$fin->savePenjualan($tgl(1, 18), 5, 'kredit', [
    ['product_id' => 1, 'qty' => 30, 'harga' => 3500, 'diskon' => 0, 'subtotal' => 105000],
    ['product_id' => 6, 'qty' => 20, 'harga' => 6000, 'diskon' => 0, 'subtotal' => 120000],
], 'Kredit guru');
$fin->savePenjualan($tgl(0, 3), 1, 'tunai', [
    ['product_id' => 9, 'qty' => 60, 'harga' => 3000, 'diskon' => 0, 'subtotal' => 180000],
    ['product_id' => 10, 'qty' => 40, 'harga' => 4000, 'diskon' => 0, 'subtotal' => 160000],
    ['product_id' => 4, 'qty' => 25, 'harga' => 2000, 'diskon' => 0, 'subtotal' => 50000],
], 'Penjualan tunai');
$fin->savePenjualan($tgl(0, 6), 6, 'tunai', [
    ['product_id' => 2, 'qty' => 20, 'harga' => 2500, 'diskon' => 0, 'subtotal' => 50000],
    ['product_id' => 5, 'qty' => 15, 'harga' => 3500, 'diskon' => 0, 'subtotal' => 52500],
], 'Penjualan tunai');

// ---------- Pemasukan lain ----------
$pemasukanCat = $pdo->query('SELECT id FROM categories WHERE type="pemasukan" ORDER BY id')->fetchAll();
$fin->savePemasukan($tgl(3, 20), (int)$pemasukanCat[1]['id'], 300000, 'Kontribusi Anggota', 'Iuran anggota bulan ke-9');
$fin->savePemasukan($tgl(2, 25), (int)$pemasukanCat[0]['id'], 150000, 'Jasa', 'Jasa fotokopi');
$fin->savePemasukan($tgl(1, 27), (int)$pemasukanCat[2]['id'], 100000, 'Pendapatan Administrasi', 'Pendaftaran anggota baru');
$fin->savePemasukan($tgl(0, 5), (int)$pemasukanCat[3]['id'], 25000, 'Bunga', 'Bunga simpanan bank');

// ---------- Pengeluaran ----------
$pengeluaranCat = $pdo->query('SELECT id FROM categories WHERE type="pengeluaran" ORDER BY id')->fetchAll();
$fin->savePengeluaran($tgl(4, 25), (int)$pengeluaranCat[0]['id'], 150000, 'PLN', 'Tagihan listrik');
$fin->savePengeluaran($tgl(3, 25), (int)$pengeluaranCat[5]['id'], 350000, 'Karyawan', 'Honor penjaga koperasi');
$fin->savePengeluaran($tgl(2, 28), (int)$pengeluaranCat[1]['id'], 75000, 'PDAM', 'Tagihan air');
$fin->savePengeluaran($tgl(1, 20), (int)$pengeluaranCat[2]['id'], 120000, 'ATK Kantor', 'Pembelian alat tulis kantor');
$fin->savePengeluaran($tgl(0, 4), (int)$pengeluaranCat[3]['id'], 50000, 'Bensin', 'Transportasi belanja');

// ---------- Bayar piutang ----------
$piutangRows = $pdo->query('SELECT * FROM receivables WHERE status="AKTIF" ORDER BY id')->fetchAll();
if (isset($piutangRows[0])) {
    $fin->bayarPiutang((int)$piutangRows[0]['id'], $tgl(2, 14), 100000, 'Angsuran piutang');
}
if (isset($piutangRows[1])) {
    $fin->bayarPiutang((int)$piutangRows[1]['id'], $tgl(0, 1), 50000, 'Angsuran');
}

// ---------- Bayar hutang ----------
$hutangRows = $pdo->query('SELECT * FROM payables WHERE status="AKTIF" ORDER BY id')->fetchAll();
if (isset($hutangRows[0])) {
    $fin->bayarHutang((int)$hutangRows[0]['id'], $tgl(2, 28), 100000, 'Angsuran hutang');
}

// ---------- Modal ----------
$pdo->prepare("INSERT INTO capital_transactions (tanggal, no_transaksi, type, nominal, keterangan, status, user_id) VALUES (?, 'MDL-0001', 'modal_awal', 5000000, 'Modal awal koperasi', 'AKTIF', 1)")->execute([$tgl(5, 1)]);

echo "Selesai. Saldo kas: " . rupiah($fin->saldoKas()) . "\n";
echo "Piutang total: " . rupiah($pdo->query('SELECT COALESCE(SUM(r.total - COALESCE((SELECT SUM(p.nominal) FROM receivable_payments p WHERE p.receivable_id = r.id AND p.status="AKTIF"),0)),0) FROM receivables r WHERE r.status="AKTIF"')->fetchColumn()) . "\n";
echo "Hutang total: " . rupiah($pdo->query('SELECT COALESCE(SUM(p.total - COALESCE((SELECT SUM(pm.nominal) FROM payable_payments pm WHERE pm.payable_id = p.id AND pm.status="AKTIF"),0)),0) FROM payables p WHERE p.status="AKTIF"')->fetchColumn()) . "\n";
echo "Buka aplikasi lalu lihat Dashboard & Laporan.\n";