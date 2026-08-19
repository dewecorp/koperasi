<?php

/**
 * Test skenario keuangan (test 1-7 sesuai spesifikasi).
 * Jalankan: php scripts/test_keuangan.php
 */

$root = dirname(__DIR__);
require_once $root . '/app/helpers/functions.php';
require_once $root . '/app/helpers/auth.php';
require_once $root . '/app/services/FinanceService.php';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'test';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Login sebagai admin sistem (tanpa user DB khusus)
$_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'name' => 'Admin Test', 'role_id' => 1, 'role_name' => 'Administrator'];

$_SESSION['flash'] = [];

$fin = new FinanceService();
$pdo = db();

$pass = 0;
$fail = 0;

function assert_eq(string $label, $actual, $expected): void
{
    global $pass, $fail;
    if ((float)$actual === (float)$expected) {
        $pass++;
        echo "PASS  $label : ". angka($actual) . "\n";
    } else {
        $fail++;
        echo "FAIL  $label : actual=" . angka($actual) . " expected=" . angka($expected) . "\n";
    }
}

function assert_str(string $label, $actual, $expected): void
{
    global $pass, $fail;
    if ((string)$actual === (string)$expected) {
        $pass++;
        echo "PASS  $label : $actual\n";
    } else {
        $fail++;
        echo "FAIL  $label : actual=$actual expected=$expected\n";
    }
}

function reset_db(): void
{
    $pdo = db();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['number_counters','cash_transactions','stock_movements','transaction_details','transactions','receivable_payments','receivables','payable_payments','payables','audit_logs'] as $t) {
        $pdo->exec("TRUNCATE TABLE $t");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    // reset stok produk dari harga awal seed
    $pdo->exec('UPDATE products SET stock = 0');
}

echo "===== TEST KEUANGAN KOPERASI =====\n";

reset_db();

// Karena seed products.stock = 0, set stok awal produk lewat penyesuaian stok
$produkSeeder = function (int $pid, float $qty) use ($pdo, $fin): void {
    $pdo->prepare('INSERT INTO stock_movements (product_id, tanggal, type, qty, keterangan, status, user_id) VALUES (?, CURDATE(), "penyesuaian", ?, "Stok awal test", "AKTIF", 1)')
        ->execute([$pid, $qty]);
    $fin->recalcStok($pid);
};
$produkSeeder(1, 100); // buku tulis
$produkSeeder(3, 150); // pulpen
$produkSeeder(9, 200); // snack

$fin->setSaldoAwal('2026-01-05', 5000000, 'Saldo awal test 2');

// TEST 1: Penjualan tunai 500rb (buku tulis 100pcs x 3500 = 350rb + pulpen 50 x 3000 = 150rb)
$fin->savePenjualan('2026-01-06', 1, 'tunai', [
    ['product_id' => 1, 'qty' => 100, 'harga' => 3500, 'diskon' => 0, 'subtotal' => 350000],
    ['product_id' => 3, 'qty' => 50,  'harga' => 3000, 'diskon' => 0, 'subtotal' => 150000],
], '');
echo "--- TEST 1: Penjualan tunai 500.000 ---\n";
assert_eq('Kas = 5.500.000', $fin->saldoKas(), 5500000);
assert_eq('Stok buku tulis = 0', $fin->stokProduk(1), 0);
assert_eq('Stok pulpen = 100', $fin->stokProduk(3), 100);

// TEST 2: Pembelian tunai 980rb
$fin->savePembelian('2026-01-07', 1, 'tunai', [
    ['product_id' => 1, 'qty' => 200, 'harga' => 2500, 'diskon' => 0, 'subtotal' => 500000],
    ['product_id' => 4, 'qty' => 250, 'harga' => 1000, 'diskon' => 0, 'subtotal' => 250000],
    ['product_id' => 5, 'qty' => 100, 'harga' => 2000, 'diskon' => 0, 'subtotal' => 200000],
    ['product_id' => 2, 'qty' => 20,  'harga' => 1500, 'diskon' => 0, 'subtotal' => 30000],
], '');
echo "--- TEST 2: Pembelian tunai 980.000 ---\n";
assert_eq('Kas = 4.520.000', $fin->saldoKas(), 4520000);
assert_eq('Stok buku tulis = 200', $fin->stokProduk(1), 200);
assert_eq('Stok penghapus = 250', $fin->stokProduk(4), 250);

// TEST 3: Penjualan kredit 200rb
$fin->savePenjualan('2026-01-08', 1, 'kredit', [
    ['product_id' => 1, 'qty' => 40, 'harga' => 3500, 'diskon' => 0, 'subtotal' => 140000],
    ['product_id' => 3, 'qty' => 20, 'harga' => 3000, 'diskon' => 0, 'subtotal' => 60000],
], '');
echo "--- TEST 3: Penjualan kredit 200.000 ---\n";
assert_eq('Kas tetap = 4.520.000', $fin->saldoKas(), 4520000);
$piutangRow = $pdo->query('SELECT * FROM receivables ORDER BY id DESC LIMIT 1')->fetch();
assert_eq('Piutang = 200.000', $fin->piutangSisa((int)$piutangRow['id']), 200000);
assert_eq('Stok buku tulis = 160', $fin->stokProduk(1), 160);

// TEST 4: Bayar piutang 100rb
$pay = $fin->bayarPiutang((int)$piutangRow['id'], '2026-01-09', 100000, 'Bayar sebagian');
echo "--- TEST 4: Bayar piutang 100.000 ---\n";
assert_eq('Kas = 4.620.000', $fin->saldoKas(), 4620000);
assert_eq('Sisa piutang = 100.000', $fin->piutangSisa((int)$piutangRow['id']), 100000);

// TEST 5: Pembelian kredit 500rb
$fin->savePembelian('2026-01-10', 2, 'kredit', [
    ['product_id' => 1, 'qty' => 100, 'harga' => 2500, 'diskon' => 0, 'subtotal' => 250000],
    ['product_id' => 9, 'qty' => 100, 'harga' => 2000, 'diskon' => 0, 'subtotal' => 200000],
    ['product_id' => 3, 'qty' => 20,  'harga' => 1800, 'diskon' => 0, 'subtotal' => 36000],
], '');
echo "--- TEST 5: Pembelian kredit 486.000 ---\n";
assert_eq('Kas tetap = 4.620.000', $fin->saldoKas(), 4620000);
$hutangRow = $pdo->query('SELECT * FROM payables ORDER BY id DESC LIMIT 1')->fetch();
assert_eq('Hutang = 486.000', $fin->hutangSisa((int)$hutangRow['id']), 486000);
assert_eq('Stok buku tulis = 260', $fin->stokProduk(1), 260);
assert_eq('Stok snack = 300', $fin->stokProduk(9), 300);

// TEST 6: Bayar hutang 200rb
$fin->bayarHutang((int)$hutangRow['id'], '2026-01-11', 200000, 'Angsuran');
echo "--- TEST 6: Bayar hutang 200.000 ---\n";
assert_eq('Kas = 4.420.000', $fin->saldoKas(), 4420000);
assert_eq('Sisa hutang = 286.000', $fin->hutangSisa((int)$hutangRow['id']), 286000);

// TEST 7: Pembatalan penjualan tunai (test 1)
$jualTunai = $pdo->query('SELECT * FROM transactions WHERE type="penjualan" ORDER BY id LIMIT 1')->fetch();
$fin->cancelTransaction((int)$jualTunai['id'], 'Test: batal transaksi');
echo "--- TEST 7: Pembatalan penjualan tunai ---\n";
assert_eq('Kas kembali (keadaan tanpa penjualan) = 3.920.000', $fin->saldoKas(), 3920000);
$after = $pdo->query('SELECT status FROM transactions WHERE id = ' . $jualTunai['id'])->fetchColumn();
assert_str('Status transaksi DIBATALKAN', $after, 'DIBATALKAN');
assert_eq('Histori masih ada (1 transaksi batal)', $pdo->query('SELECT COUNT(*) FROM transactions WHERE id = ' . $jualTunai['id'])->fetchColumn(), 1);
$beranjak = $pdo->query('SELECT status FROM cash_transactions WHERE related_id = ' . $jualTunai['id'] . ' AND related_type="transactions" LIMIT 1')->fetchColumn();
assert_str('Kas transaksi dibatalkan', $beranjak, 'DIBATALKAN');
assert_eq('Stok buku tulis kembali = 360', $fin->stokProduk(1), 360);

// TEST penjualan melebihi stok -> harus gagal
echo "--- TEST validasi: penjualan melebihi stok ---\n";
try {
    $fin->savePenjualan('2026-01-12', null, 'tunai', [
        ['product_id' => 1, 'qty' => 99999, 'harga' => 3500, 'diskon' => 0, 'subtotal' => 349996500],
    ], '');
    assert_eq('Penjualan melebihi stok DITOLAK', 0, 1);
} catch (Throwable $e) {
    $pass++;
    echo "PASS  Penjualan melebihi stok ditolak : {$e->getMessage()}\n";
}

// TEST pembayaran piutang melebihi sisa -> gagal
echo "--- TEST validasi: bayar piutang melebihi sisa ---\n";
try {
    $fin->bayarPiutang((int)$piutangRow['id'], '2026-01-12', 999999, 'x');
    assert_eq('Bayar piutang berlebih DITOLAK', 0, 1);
} catch (Throwable $e) {
    $pass++;
    echo "PASS  Bayar piutang berlebih ditolak : {$e->getMessage()}\n";
}

echo "\n===== HASIL: $pass PASS, $fail FAIL =====\n";
exit($fail > 0 ? 1 : 0);