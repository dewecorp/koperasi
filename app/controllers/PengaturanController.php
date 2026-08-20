<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class PengaturanController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator']);
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                flash('error', 'Token keamanan tidak valid.');
                redirect('pengaturan');
            }
            $tahunLama = setting('tahun_ajaran_aktif', '');
            $tahunBaru = input('tahun_ajaran_aktif', '');
            $keys = [
                'allow_negative_cash' => input('allow_negative_cash', '0') === '1' ? '1' : '0',
                'allow_negative_stock' => input('allow_negative_stock', '0') === '1' ? '1' : '0',
                'saldo_minimum_cash' => (string)max(0, (float)input('saldo_minimum_cash', 0)),
                'tahun_ajaran_aktif' => $tahunBaru,
            ];
            foreach ($keys as $key => $value) {
                $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')
                    ->execute([$key, $value]);
            }

            // Bawa saldo kas tahun ajaran lama ke tahun ajaran baru
            $diBawa = false;
            if ($tahunLama !== '' && $tahunBaru !== '' && $tahunLama !== $tahunBaru) {
                try {
                    $diBawa = (new FinanceService())->carryForwardSaldo($tahunLama, $tahunBaru);
                } catch (Throwable $e) {
                    flash('error', 'Gagal membawa saldo kas: ' . $e->getMessage());
                    redirect('pengaturan');
                }
            }

            audit_log('UBAH PENGATURAN', json_encode($keys));
            if ($diBawa) {
                flash('success', 'Pengaturan disimpan. Saldo kas tahun ajaran ' . e($tahunLama) . ' dibawa sebagai saldo awal tahun ajaran ' . e($tahunBaru) . '.');
            } else {
                flash('success', 'Pengaturan disimpan.');
            }
            redirect('pengaturan');
        }

        $currentYear = (int)date('Y');
        $tahunOptions = [];
        for ($i = -1; $i <= 3; $i++) {
            $y = $currentYear + $i;
            $tahunOptions[] = "$y/" . ($y + 1);
        }

        $this->render('pengaturan/index', [
            'pageTitle' => 'Pengaturan Aplikasi',
            'set' => [
                'allow_negative_cash' => setting('allow_negative_cash', '0'),
                'allow_negative_stock' => setting('allow_negative_stock', '0'),
                'saldo_minimum_cash' => setting('saldo_minimum_cash', '500000'),
                'tahun_ajaran_aktif' => setting('tahun_ajaran_aktif', $tahunOptions[1] ?? ''),
            ],
            'tahunOptions' => $tahunOptions,
            'tahunBerisi' => $this->tahunAjaranDenganData(),
        ]);
    }

    /** Hapus seluruh data transaksi milik satu tahun ajaran (permanen). */
    public function hapusTahunAjaran(): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pengaturan');
        }

        $tahunAjaran = input('tahun_ajaran_hapus', '');
        $pdo = db();

        if ($tahunAjaran === '' || !preg_match('/^\d{4}\/\d{4}$/', $tahunAjaran)) {
            flash('error', 'Tahun ajaran tidak valid.');
            redirect('pengaturan');
        }
        if ($tahunAjaran === tahun_ajaran_aktif()) {
            flash('error', 'Tahun ajaran aktif tidak dapat dihapus. Pilih tahun ajaran lain dahulu di pengaturan.');
            redirect('pengaturan');
        }

        // Cek apakah ada data transaksi pada tahun ajaran tsb
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE tahun_ajaran = ?');
        $stmt->execute([$tahunAjaran]);
        $count = (int)$stmt->fetchColumn();
        if ($count === 0) {
            flash('error', 'Tidak ada data transaksi pada tahun ajaran ' . e($tahunAjaran) . '.');
            redirect('pengaturan');
        }

        // Kumpulkan produk yang terpengaruh untuk hitung ulang stok
        $stmt = $pdo->prepare(
            'SELECT DISTINCT td.product_id FROM transaction_details td
             JOIN transactions t ON t.id = td.transaction_id WHERE t.tahun_ajaran = ?'
        );
        $stmt->execute([$tahunAjaran]);
        $productIds = array_map(fn($r) => (int)$r['product_id'], $stmt->fetchAll());

        // Nomor transaksi pada tahun ajaran tsb (untuk hapus stok movements)
        $stmt = $pdo->prepare('SELECT no_transaksi FROM transactions WHERE tahun_ajaran = ?');
        $stmt->execute([$tahunAjaran]);
        $noList = array_map(fn($r) => $r['no_transaksi'], $stmt->fetchAll());

        $pdo->beginTransaction();
        try {
            $t = $tahunAjaran;

            // Hapus detail transaksi
            $pdo->prepare(
                'DELETE FROM transaction_details WHERE transaction_id IN (SELECT id FROM transactions WHERE tahun_ajaran = ?)'
            )->execute([$t]);

            // Hapus pembayaran piutang & piutang
            $pdo->prepare(
                'DELETE FROM receivable_payments WHERE receivable_id IN (SELECT id FROM receivables WHERE tahun_ajaran = ?)'
            )->execute([$t]);
            $pdo->prepare('DELETE FROM receivables WHERE tahun_ajaran = ?')->execute([$t]);

            // Hapus pembayaran hutang & hutang
            $pdo->prepare(
                'DELETE FROM payable_payments WHERE payable_id IN (SELECT id FROM payables WHERE tahun_ajaran = ?)'
            )->execute([$t]);
            $pdo->prepare('DELETE FROM payables WHERE tahun_ajaran = ?')->execute([$t]);

            // Hapus kas, modal, stok movements
            $pdo->prepare('DELETE FROM cash_transactions WHERE tahun_ajaran = ?')->execute([$t]);
            $pdo->prepare('DELETE FROM capital_transactions WHERE tahun_ajaran = ?')->execute([$t]);
            if (!empty($noList)) {
                $pdo->prepare(
                    'DELETE FROM stock_movements WHERE no_referensi IN (' . implode(',', array_fill(0, count($noList), '?')) . ')'
                )->execute($noList);
            }

            // Hapus bukti (attachments) milik transaksi tsb
            $pdo->prepare(
                'DELETE FROM attachments WHERE related_type = "transactions" AND related_id IN (SELECT id FROM transactions WHERE tahun_ajaran = ?)'
            )->execute([$t]);

            // Hapus transaksi
            $pdo->prepare('DELETE FROM transactions WHERE tahun_ajaran = ?')->execute([$t]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal menghapus data: ' . $e->getMessage());
            redirect('pengaturan');
        }

        // Hitung ulang stok produk yang terpengaruh
        foreach ($productIds as $pid) {
            $this->recalcStok($pid);
        }

        audit_log('HAPUS DATA TAHUN AJARAN', $tahunAjaran . ' (' . $count . ' transaksi)');
        flash('success', 'Seluruh data transaksi tahun ajaran ' . e($tahunAjaran) . ' telah dihapus.');
        redirect('pengaturan');
    }

    private function tahunAjaranDenganData(): array
    {
        $stmt = db()->query(
            'SELECT tahun_ajaran, COUNT(*) AS jml FROM transactions GROUP BY tahun_ajaran ORDER BY tahun_ajaran DESC'
        );
        return $stmt->fetchAll();
    }

    private function recalcStok(int $productId): void
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(CASE WHEN type="masuk" THEN qty WHEN type="keluar" THEN -qty WHEN type="penyesuaian" THEN qty END),0)
             FROM stock_movements WHERE product_id = ? AND status="AKTIF"'
        );
        $stmt->execute([$productId]);
        $pdo->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute([$stmt->fetchColumn(), $productId]);
    }
}