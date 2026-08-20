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

    /**
     * Perbarui kode aplikasi dari repositori resmi (amati: hanya file kode).
     * File sensitif (konfigurasi, database, upload, backup, storage) TIDAK pernah ditimpa,
     * sehingga data & pengaturan lokal selalu aman dan database tidak pernah di-reset.
     */
    public function updateSistem(): void
    {
        $this->guard(['Administrator']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            $this->renderJson(['ok' => false, 'message' => 'Permintaan tidak valid.']);
        }

        @set_time_limit(180);
        $repoZip = 'https://codeload.github.com/dewecorp/koperasi/zip/refs/heads/main';
        $root = APP_ROOT;
        $tmpBase = sys_get_temp_dir();
        $stamp = date('Ymd_His');
        $zipPath = $tmpBase . '/kop_update_' . $stamp . '.zip';
        $extractDir = $tmpBase . '/kop_extract_' . $stamp;

        // 1) Unduh arsip
        $ok = false;
        $bin = @file_get_contents($repoZip, false);
        if ($bin === false && function_exists('curl_init')) {
            $ch = curl_init($repoZip);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 120,
            ]);
            $bin = curl_exec($ch);
            curl_close($ch);
        }
        if ($bin === false || strlen($bin) < 100 || substr($bin, 0, 2) !== 'PK') {
            @unlink($zipPath);
            $this->renderJson(['ok' => false, 'message' => 'Gagal mengunduh pembaruan.']);
        }
        file_put_contents($zipPath, $bin);

        // 2) Ekstrak
        if (!class_exists('ZipArchive')) {
            @unlink($zipPath);
            $this->renderJson(['ok' => false, 'message' => 'Ekstensi ZIP tidak tersedia di server ini.']);
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            @unlink($zipPath);
            $this->renderJson(['ok' => false, 'message' => 'Arsip pembaruan rusak.']);
        }
        $zip->extractTo($extractDir);
        $zip->close();
        @unlink($zipPath);

        // 3) Verifikasi isi: pastikan ini benar-benar proyek aplikasi ini
        $base = null;
        $entries = @scandir($extractDir);
        if ($entries) {
            foreach ($entries as $e) {
                if ($e === '.' || $e === '..') continue;
                $candidate = $extractDir . '/' . $e;
                if (is_dir($candidate) && file_exists($candidate . '/app/controllers/DashboardController.php')) {
                    $base = $candidate;
                    break;
                }
            }
        }
        if ($base === null) {
            $this->removeDir($extractDir);
            $this->renderJson(['ok' => false, 'message' => 'Sumber pembaruan tidak dikenali. Dihentikan demi keamanan.']);
        }

        // 4) String terlarang di file kode (cegah backdoor jelas)
        $terlarangPatterns = ['eval(', 'base64_decode(', 'shell_exec', 'passthru', 'system(', 'exec(', 'popen(', 'assert(', 'include $_', 'file_get_contents("http'];
        $dirtyFiles = [];
        $this->scanDirty($base, $root, $terlarangPatterns, $dirtyFiles, 0);
        if (!empty($dirtyFiles)) {
            $this->removeDir($extractDir);
            $this->renderJson(['ok' => false, 'message' => 'Pembaruan berisi kode mencurigakan dan ditolak: ' . implode(', ', array_slice($dirtyFiles, 0, 5))]);
        }

        // 5) Backup kondisi saat ini (file kode saja)
        $backupDir = $root . '/storage/backups/update_' . $stamp;
        @mkdir($backupDir, 0775, true);

        // 6) Salin file kode baru, LEWATKAN semua path sensitif
        $lewatkan = ['config', 'database', 'storage', '.git', '.env', 'public/uploads', 'public/_keuangan', 'public/_laporan', 'public/_log', 'public/_master', 'public/_pengaturan', 'public/_transaksi'];
        $disalin = 0;
        $gagal = [];
        $this->salurkan($base, $root, $backupDir, $lewatkan, $disalin, $gagal);

        // 7) Bersihkan & lapor
        $this->removeDir($extractDir);
        if (!empty($gagal)) {
            $this->renderJson(['ok' => false, 'message' => 'Pembaruan sebagian gagal pada: ' . implode(', ', array_slice($gagal, 0, 5))]);
        }
        audit_log('UPDATE SISTEM', $disalin . ' file diperbarui');
        $this->renderJson(['ok' => true, 'message' => 'Pembaruan selesai. ' . $disalin . ' file diperbarui. Data aplikasi tetap aman.']);
    }

    /** Salin file dari sumber ke target, backup file lama, lewati path sensitif. */
    private function salurkan(string $src, string $dstRoot, string $backupDir, array $skip, int &$count, array &$failed): void
    {
        $skipFull = [];
        foreach ($skip as $s) {
            $skipFull[str_replace('/', DIRECTORY_SEPARATOR, $s)] = true;
        }
        $items = @scandir($src) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $rel = $item;
            $srcPath = $src . '/' . $item;
            $dstPath = $dstRoot . '/' . $item;
            if (isset($skipFull[$item])) continue;

            if (is_dir($srcPath)) {
                if (!is_dir($dstPath)) @mkdir($dstPath, 0775, true);
                // path relatif di bawah level akar ikut dilindungi
                $subSkip = array_filter(array_keys($skipFull), fn($k) => strpos($k, $item . DIRECTORY_SEPARATOR) === 0);
                $subSkip = array_map(fn($k) => substr($k, strlen($item) + 1), $subSkip);
                $this->salurkan($srcPath, $dstPath, $backupDir . '/' . $item, $subSkip, $count, $failed);
            } else {
                // backup file lama ke storage, lalu timpa dengan versi baru
                if (file_exists($dstPath)) {
                    @mkdir(dirname($backupDir . '/' . $item), 0775, true);
                    @copy($dstPath, $backupDir . '/' . $item);
                }
                if (!@copy($srcPath, $dstPath)) {
                    $failed[] = $item;
                    continue;
                }
                $count++;
            }
        }
    }

    /** Pindai file PHP untuk panggilan fungsi berbahaya (anti backdoor). */
    private function scanDirty(string $dir, string $root, array $patterns, array &$dirty, int $depth): void
    {
        if ($depth > 6) return;
        $items = @scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                if (in_array($item, ['node_modules', '.git'], true)) continue;
                $this->scanDirty($path, $root, $patterns, $dirty, $depth + 1);
            } elseif (preg_match('/\.php$/i', $item)) {
                if ($this->phpMencurigakan($path)) {
                    $dirty[] = str_replace($root, '', $path);
                }
            }
        }
    }

    /** Deteksi panggilan fungsi PHP berbahaya via tokenizer (abaikan ->method()/::static()). */
    private function phpMencurigakan(string $path): bool
    {
        $content = @file_get_contents($path);
        if ($content === false || $content === '') return false;
        $tokens = @token_get_all($content);
        if (!is_array($tokens)) return false;

        $danger = ['eval', 'base64_decode', 'shell_exec', 'passthru', 'system', 'exec', 'popen', 'proc_open', 'pcntl_exec', 'assert'];
        $n = count($tokens);

        for ($i = 0; $i < $n; $i++) {
            $tok = $tokens[$i];

            // include/require variabel superglobal = celah LFI/backdoor
            if (is_array($tok) && in_array($tok[0], [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
                $j = $i + 1;
                while ($j < $n && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $j++;
                }
                if ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_VARIABLE) {
                    $varName = $tokens[$j][1];
                    if (in_array($varName, ['$_GET', '$_POST', '$_REQUEST', '$_FILES', '$_COOKIE', '$_SERVER', '$_ENV', '$GLOBALS'], true)) {
                        return true;
                    }
                }
                continue;
            }

            if (!is_array($tok) || $tok[0] !== T_STRING) continue;
            $name = strtolower($tok[1]);
            if (!in_array($name, $danger, true)) continue;

            // Abaikan bila method/properti (->x() / ::x()) atau deklarasi fungsi
            $j = $i - 1;
            while ($j >= 0 && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $j--;
            }
            if ($j >= 0) {
                $p = $tokens[$j];
                $isMethod = (is_array($p) && in_array($p[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_FUNCTION], true))
                    || (!is_array($p) && ($p === '->' || $p === '::'));
                if ($isMethod) continue;
            }

            // Harus dipanggil: diikuti "("
            $next = $i + 1 < $n ? $tokens[$i + 1] : null;
            if (!((is_array($next) && $next[1] === '(') || $next === '(')) continue;

            return true;
        }
        return false;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = @scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $p = $dir . '/' . $item;
            is_dir($p) ? $this->removeDir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}