<?php

/**
 * Layanan transaksi keuangan.
 * Semua operasi yang memengaruhi beberapa tabel dibungkus BEGIN/COMMIT/ROLLBACK
 * agar kas, stok, piutang, dan hutang selalu konsisten.
 */

require_once APP_ROOT . '/app/core/Model.php';

class FinanceService extends Model
{
    /** ID user aktif (untuk catatan pembuat transaksi). */
    private function uid(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    }

    /* ============================================================
     * SALDO KAS
     * ============================================================ */

    /** Saldo kas sampai tanggal tertentu (default: semua sampai hari ini). */
    public function saldoKas(?string $tanggalSampai = null, ?string $tahunAjaran = null): float
    {
        if ($tahunAjaran === null) {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN jenis='saldo_awal' AND status='AKTIF' THEN nominal END),0)
                    + COALESCE(SUM(CASE WHEN jenis='masuk'   AND status='AKTIF' THEN nominal END),0)
                    - COALESCE(SUM(CASE WHEN jenis='keluar'  AND status='AKTIF' THEN nominal END),0) AS saldo
                FROM cash_transactions
                WHERE tahun_ajaran = ?";
        $params = [$tahunAjaran];
        if ($tanggalSampai) {
            $sql .= " AND tanggal <= ?";
            $params[] = $tanggalSampai;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    public function totalMasuk(?string $dari, ?string $sampai, ?string $tahunAjaran = null): float
    {
        if ($tahunAjaran === null) {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        return (float)$this->value(
            'SELECT COALESCE(SUM(nominal),0) FROM cash_transactions WHERE tahun_ajaran = ? AND jenis="masuk" AND status="AKTIF" AND tanggal BETWEEN ? AND ?',
            [$tahunAjaran, $dari, $sampai]
        );
    }

    public function totalKeluar(?string $dari, ?string $sampai, ?string $tahunAjaran = null): float
    {
        if ($tahunAjaran === null) {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        return (float)$this->value(
            'SELECT COALESCE(SUM(nominal),0) FROM cash_transactions WHERE tahun_ajaran = ? AND jenis="keluar" AND status="AKTIF" AND tanggal BETWEEN ? AND ?',
            [$tahunAjaran, $dari, $sampai]
        );
    }

    /** Nilai saldo awal saat ini (row tunggal jenis saldo_awal). */
    public function saldoAwalRow(?string $tahunAjaran = null): ?array
    {
        if ($tahunAjaran === null) {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        return $this->one(
            'SELECT * FROM cash_transactions WHERE tahun_ajaran = ? AND jenis="saldo_awal" ORDER BY id DESC LIMIT 1',
            [$tahunAjaran]
        );
    }

    /** Simpan/ubah saldo awal (satu baris). */
    public function setSaldoAwal(string $tanggal, float $nominal, string $keterangan = '', ?string $tahunAjaran = null): void
    {
        if ($tahunAjaran === null) {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        $this->pdo->beginTransaction();
        try {
            $existing = $this->saldoAwalRow($tahunAjaran);
            if ($existing) {
                $this->execute(
                    'UPDATE cash_transactions SET tanggal = ?, nominal = ?, keterangan = ?, updated_at = NOW() WHERE id = ?',
                    [$tanggal, $nominal, $keterangan, $existing['id']]
                );
            } else {
                $noSaldo = 'SA-' . str_replace('/', '', $tahunAjaran);
                $this->execute(
                    'INSERT INTO cash_transactions (tahun_ajaran, tanggal, no_transaksi, jenis, kategori, nominal, keterangan, status, user_id)
                     VALUES (?, ?, ?, "saldo_awal", "Saldo Awal", ?, ?, "AKTIF", ?)',
                    [$tahunAjaran, $tanggal, $noSaldo, $nominal, $keterangan, $this->uid()]
                );
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Bawa saldo kas akhir tahun ajaran lama menjadi saldo awal tahun ajaran baru.
     * Otomatis dibuat sekali; bila tahun baru sudah punya saldo awal, tidak diubah.
     */
    public function carryForwardSaldo(string $tahunLama, string $tahunBaru, string $tanggal = ''): bool
    {
        if ($tahunLama === '' || $tahunBaru === '' || $tahunLama === $tahunBaru) {
            return false;
        }
        if ($this->saldoAwalRow($tahunBaru) !== null) {
            return false;
        }
        if ($tanggal === '') {
            $tanggal = date('Y-m-d');
        }
        $saldoLama = $this->saldoKas(null, $tahunLama);
        $this->setSaldoAwal($tanggal, $saldoLama, 'Saldo kas tahun ajaran ' . $tahunLama . ' dibawa ke tahun ajaran ' . $tahunBaru, $tahunBaru);
        return true;
    }

    /** Buku kas lengkap dengan saldo berjalan. */
    public function bukuKas(string $dari, string $sampai, ?string $tahunAjaran = null): array
    {
        if ($tahunAjaran === null) {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        // Seluruh mutasi aktif sampai akhir periode (termasuk saldo awal),
        // diurutkan sesuai kejadian untuk saldo berjalan yang benar.
        $all = $this->all(
            'SELECT * FROM cash_transactions
             WHERE tahun_ajaran = ? AND status="AKTIF" AND tanggal <= ?
             ORDER BY tanggal, id',
            [$tahunAjaran, $sampai]
        );

        $saldoAwalNominal = 0;
        $saldo = 0;
        $rows = [];
        $saldoAwalRow = null;

        foreach ($all as $r) {
            if ($r['jenis'] === 'saldo_awal') {
                $saldo += (float)$r['nominal'];
                $saldoAwalNominal += (float)$r['nominal'];
                $r['saldo'] = $saldo;
                $saldoAwalRow = $r;
                continue;
            }
            if ($r['jenis'] === 'masuk') {
                $saldo += (float)$r['nominal'];
            }
            if ($r['jenis'] === 'keluar') {
                $saldo -= (float)$r['nominal'];
            }
            $r['saldo'] = $saldo;
            // Baris periode yang ditampilkan
            if ($r['tanggal'] >= $dari && $r['tanggal'] <= $sampai) {
                $rows[] = $r;
            }
        }

        // Baris pembuka "Saldo Awal" selalu tampil (meski tanggalnya sebelum periode)
        if ($saldoAwalRow !== null) {
            array_unshift($rows, $saldoAwalRow);
        }

        return [
            'saldo_awal' => $saldoAwalNominal,
            'rows' => $rows,
            'saldo_akhir' => $saldo,
        ];
    }

    /* ============================================================
     * STOK
     * ============================================================ */

    /** Hitung ulang stok produk berdasarkan mutasi aktif. */
    public function recalcStok(int $productId): void
    {
        $stok = (float)$this->value(
            'SELECT COALESCE(SUM(CASE
                WHEN type="masuk" THEN qty
                WHEN type="keluar" THEN -qty
                WHEN type="penyesuaian" THEN qty
            END),0) FROM stock_movements
            WHERE product_id = ? AND status="AKTIF"',
            [$productId]
        );
        $this->execute('UPDATE products SET stock = ? WHERE id = ?', [$stok, $productId]);
    }

    public function stokProduk(int $productId): float
    {
        return (float)$this->value('SELECT stock FROM products WHERE id = ?', [$productId]);
    }

    /* ============================================================
     * PENJUALAN
     * ============================================================ */

    /**
     * @param array $items [['product_id'=>, 'qty'=>, 'harga'=>, 'diskon'=>]]
     */
    public function savePenjualan(string $tanggal, ?int $customerId, string $metode, array $items, string $keterangan = '', string $tahunAjaran = ''): int
    {
        if (empty($items)) {
            throw new RuntimeException('Transaksi tanpa detail barang.');
        }
        if ($tahunAjaran === '') {
            $tahunAjaran = tahun_ajaran_aktif();
        }

        $this->pdo->beginTransaction();
        try {
            $no = nomor_transaksi('PJ', $tanggal);
            $total = 0;
            foreach ($items as $i) {
                $total += (float)$i['subtotal'];
            }

            $this->execute(
                'INSERT INTO transactions (no_transaksi, type, tahun_ajaran, tanggal, customer_id, total, payment_method, keterangan, user_id)
                 VALUES (?, "penjualan", ?, ?, ?, ?, ?, ?, ?)',
                [$no, $tahunAjaran, $tanggal, $customerId, $total, $metode, $keterangan, $this->uid()]
            );
            $txId = $this->lastId();

            foreach ($items as $i) {
                $pid = (int)$i['product_id'];
                $qty = (float)$i['qty'];
                $harga = (float)$i['harga'];
                $diskon = (float)($i['diskon'] ?? 0);
                $subtotal = (float)$i['subtotal'];

                $this->execute(
                    'INSERT INTO transaction_details (transaction_id, product_id, qty, harga, diskon, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [$txId, $pid, $qty, $harga, $diskon, $subtotal]
                );

                // Cek stok
                $stok = $this->stokProduk($pid);
                if ($qty > $stok) {
                    $produk = $this->one('SELECT name FROM products WHERE id = ?', [$pid]);
                    throw new RuntimeException('Stok tidak cukup untuk "' . ($produk['name'] ?? '') . '". Stok: ' . angka($stok) . ', diminta: ' . angka($qty));
                }
                if (setting('allow_negative_stock', '0') === '0' && $qty > $stok) {
                    throw new RuntimeException('Stok tidak mencukupi.');
                }

                $this->execute(
                    'INSERT INTO stock_movements (product_id, tahun_ajaran, tanggal, no_referensi, type, qty, keterangan, status, user_id)
                     VALUES (?, ?, ?, ?, "keluar", ?, ?, "AKTIF", ?)',
                    [$pid, $tahunAjaran, $tanggal, $no, $qty, 'Penjualan ' . $no, $this->uid()]
                );
                $this->recalcStok($pid);
            }

            if ($metode === 'tunai') {
                $this->execute(
                    'INSERT INTO cash_transactions (tahun_ajaran, tanggal, no_transaksi, jenis, kategori, nominal, keterangan, status, related_type, related_id, user_id)
                     VALUES (?, ?, ?, "masuk", "Penjualan", ?, ?, "AKTIF", "transactions", ?, ?)',
                    [$tahunAjaran, $tanggal, $no, $total, 'Penjualan tunai ' . $no, $txId, $this->uid()]
                );
            } else {
                // Kredit -> piutang
                $this->execute(
                    'INSERT INTO receivables (customer_id, transaction_id, tahun_ajaran, tanggal, no_transaksi, total, status)
                     VALUES (?, ?, ?, ?, ?, ?, "AKTIF")',
                    [$customerId, $txId, $tahunAjaran, $tanggal, $no, $total]
                );
            }

            $this->pdo->commit();
            return $txId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ============================================================
     * PEMBELIAN
     * ============================================================ */

    public function savePembelian(string $tanggal, ?int $supplierId, string $metode, array $items, string $keterangan = '', string $tahunAjaran = ''): int
    {
        if (empty($items)) {
            throw new RuntimeException('Transaksi tanpa detail barang.');
        }
        if ($tahunAjaran === '') {
            $tahunAjaran = tahun_ajaran_aktif();
        }

        $this->pdo->beginTransaction();
        try {
            $no = nomor_transaksi('PB', $tanggal);
            $total = 0;
            foreach ($items as $i) {
                $total += (float)$i['subtotal'];
            }

            $this->execute(
                'INSERT INTO transactions (no_transaksi, type, tahun_ajaran, tanggal, supplier_id, total, payment_method, keterangan, user_id)
                 VALUES (?, "pembelian", ?, ?, ?, ?, ?, ?, ?)',
                [$no, $tahunAjaran, $tanggal, $supplierId, $total, $metode, $keterangan, $this->uid()]
            );
            $txId = $this->lastId();

            foreach ($items as $i) {
                $pid = (int)$i['product_id'];
                $qty = (float)$i['qty'];
                $harga = (float)$i['harga'];
                $subtotal = (float)$i['subtotal'];

                $this->execute(
                    'INSERT INTO transaction_details (transaction_id, product_id, qty, harga, diskon, subtotal)
                     VALUES (?, ?, ?, ?, 0, ?)',
                    [$txId, $pid, $qty, $harga, $subtotal]
                );

                $this->execute(
                    'INSERT INTO stock_movements (product_id, tahun_ajaran, tanggal, no_referensi, type, qty, keterangan, status, user_id)
                     VALUES (?, ?, ?, ?, "masuk", ?, ?, "AKTIF", ?)',
                    [$pid, $tahunAjaran, $tanggal, $no, $qty, 'Pembelian ' . $no, $this->uid()]
                );
                $this->recalcStok($pid);
            }

            if ($metode === 'tunai') {
                $this->checkSaldoCukup($total);
                $this->execute(
                    'INSERT INTO cash_transactions (tahun_ajaran, tanggal, no_transaksi, jenis, kategori, nominal, keterangan, status, related_type, related_id, user_id)
                     VALUES (?, ?, ?, "keluar", "Pembelian", ?, ?, "AKTIF", "transactions", ?, ?)',
                    [$tahunAjaran, $tanggal, $no, $total, 'Pembelian tunai ' . $no, $txId, $this->uid()]
                );
            } else {
                $this->execute(
                    'INSERT INTO payables (supplier_id, transaction_id, tahun_ajaran, tanggal, no_transaksi, total, status)
                     VALUES (?, ?, ?, ?, ?, ?, "AKTIF")',
                    [$supplierId, $txId, $tahunAjaran, $tanggal, $no, $total]
                );
            }

            $this->pdo->commit();
            return $txId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ============================================================
     * PEMASUKAN & PENGELUARAN
     * ============================================================ */

    public function savePemasukan(string $tanggal, int $kategoriId, float $nominal, string $sumber, string $keterangan, string $tahunAjaran = ''): int
    {
        if ($tahunAjaran === '') {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        $this->pdo->beginTransaction();
        try {
            $no = nomor_transaksi('KM', $tanggal);
            $this->execute(
                'INSERT INTO transactions (no_transaksi, type, tahun_ajaran, tanggal, category_id, total, keterangan, user_id)
                 VALUES (?, "pemasukan", ?, ?, ?, ?, ?, ?)',
                [$no, $tahunAjaran, $tanggal, $kategoriId, $nominal, $keterangan, $this->uid()]
            );
            $txId = $this->lastId();

            $this->execute(
                'INSERT INTO cash_transactions (tahun_ajaran, tanggal, no_transaksi, jenis, kategori, nominal, keterangan, status, related_type, related_id, user_id)
                 VALUES (?, ?, ?, "masuk", ?, ?, ?, "AKTIF", "transactions", ?, ?)',
                [$tahunAjaran, $tanggal, $no, $sumber, $nominal, $keterangan, $txId, $this->uid()]
            );

            $this->pdo->commit();
            return $txId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function savePengeluaran(string $tanggal, int $kategoriId, float $nominal, string $penerima, string $keterangan, string $tahunAjaran = ''): int
    {
        if ($tahunAjaran === '') {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        $this->pdo->beginTransaction();
        try {
            $this->checkSaldoCukup($nominal);

            $no = nomor_transaksi('KK', $tanggal);
            $this->execute(
                'INSERT INTO transactions (no_transaksi, type, tahun_ajaran, tanggal, category_id, total, keterangan, user_id)
                 VALUES (?, "pengeluaran", ?, ?, ?, ?, ?, ?)',
                [$no, $tahunAjaran, $tanggal, $kategoriId, $nominal, $keterangan, $this->uid()]
            );
            $txId = $this->lastId();

            $this->execute(
                'INSERT INTO cash_transactions (tahun_ajaran, tanggal, no_transaksi, jenis, kategori, nominal, keterangan, status, related_type, related_id, user_id)
                 VALUES (?, ?, ?, "keluar", ?, ?, ?, "AKTIF", "transactions", ?, ?)',
                [$tahunAjaran, $tanggal, $no, $penerima, $nominal, $keterangan, $txId, $this->uid()]
            );

            $this->pdo->commit();
            return $txId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Cek saldo kas cukup (bila pengaturan melarang saldo negatif). */
    public function checkSaldoCukup(float $nominal): void
    {
        if (setting('allow_negative_cash', '0') === '1') {
            return;
        }
        $saldo = $this->saldoKas();
        if ($nominal > $saldo) {
            throw new RuntimeException('Saldo kas tidak cukup. Saldo saat ini: ' . rupiah($saldo));
        }
    }

    /* ============================================================
     * PIUTANG & HUTANG
     * ============================================================ */

    public function piutangSisa(int $receivableId): float
    {
        $row = $this->one('SELECT * FROM receivables WHERE id = ?', [$receivableId]);
        if (!$row || $row['status'] === 'DIBATALKAN') {
            return 0;
        }
        $dibayar = (float)$this->value(
            'SELECT COALESCE(SUM(nominal),0) FROM receivable_payments WHERE receivable_id = ? AND status="AKTIF"',
            [$receivableId]
        );
        return max(0, (float)$row['total'] - $dibayar);
    }

    public function hutangSisa(int $payableId): float
    {
        $row = $this->one('SELECT * FROM payables WHERE id = ?', [$payableId]);
        if (!$row || $row['status'] === 'DIBATALKAN') {
            return 0;
        }
        $dibayar = (float)$this->value(
            'SELECT COALESCE(SUM(nominal),0) FROM payable_payments WHERE payable_id = ? AND status="AKTIF"',
            [$payableId]
        );
        return max(0, (float)$row['total'] - $dibayar);
    }

    public function bayarPiutang(int $receivableId, string $tanggal, float $nominal, string $keterangan = '', string $tahunAjaran = ''): int
    {
        if ($tahunAjaran === '') {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        $this->pdo->beginTransaction();
        try {
            $sisa = $this->piutangSisa($receivableId);
            if ($nominal <= 0) {
                throw new RuntimeException('Nominal harus lebih dari 0.');
            }
            if ($nominal > $sisa) {
                throw new RuntimeException('Pembayaran melebihi sisa piutang (' . rupiah($sisa) . ').');
            }

            $no = nomor_transaksi('BYR-PIU', $tanggal);
            $this->execute(
                'INSERT INTO receivable_payments (receivable_id, tanggal, no_bukti, nominal, keterangan, status, user_id)
                 VALUES (?, ?, ?, ?, ?, "AKTIF", ?)',
                [$receivableId, $tanggal, $no, $nominal, $keterangan, $this->uid()]
            );
            $payId = $this->lastId();

            $this->execute(
                'INSERT INTO cash_transactions (tahun_ajaran, tanggal, no_transaksi, jenis, kategori, nominal, keterangan, status, related_type, related_id, user_id)
                 VALUES (?, ?, ?, "masuk", "Pembayaran Piutang", ?, ?, "AKTIF", "receivable_payments", ?, ?)',
                [$tahunAjaran, $tanggal, $no, $nominal, $keterangan, $payId, $this->uid()]
            );

            $this->pdo->commit();
            return $payId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function bayarHutang(int $payableId, string $tanggal, float $nominal, string $keterangan = '', string $tahunAjaran = ''): int
    {
        if ($tahunAjaran === '') {
            $tahunAjaran = tahun_ajaran_aktif();
        }
        $this->pdo->beginTransaction();
        try {
            $sisa = $this->hutangSisa($payableId);
            if ($nominal <= 0) {
                throw new RuntimeException('Nominal harus lebih dari 0.');
            }
            if ($nominal > $sisa) {
                throw new RuntimeException('Pembayaran melebihi sisa hutang (' . rupiah($sisa) . ').');
            }
            $this->checkSaldoCukup($nominal);

            $no = nomor_transaksi('BYR-HUT', $tanggal);
            $this->execute(
                'INSERT INTO payable_payments (payable_id, tanggal, no_bukti, nominal, keterangan, status, user_id)
                 VALUES (?, ?, ?, ?, ?, "AKTIF", ?)',
                [$payableId, $tanggal, $no, $nominal, $keterangan, $this->uid()]
            );
            $payId = $this->lastId();

            $this->execute(
                'INSERT INTO cash_transactions (tahun_ajaran, tanggal, no_transaksi, jenis, kategori, nominal, keterangan, status, related_type, related_id, user_id)
                 VALUES (?, ?, ?, "keluar", "Pembayaran Hutang", ?, ?, "AKTIF", "payable_payments", ?, ?)',
                [$tahunAjaran, $tanggal, $no, $nominal, $keterangan, $payId, $this->uid()]
            );

            $this->pdo->commit();
            return $payId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ============================================================
     * PEMBATALAN TRANSAKSI (soft cancel, efek dibalik otomatis)
     * ============================================================ */

    /** Batalkan transaksi utama (penjualan/pembelian/pemasukan/pengeluaran). */
    public function cancelTransaction(int $txId, string $alasan): void
    {
        if (trim($alasan) === '') {
            throw new RuntimeException('Alasan pembatalan wajib diisi.');
        }

        $tx = $this->one('SELECT * FROM transactions WHERE id = ?', [$txId]);
        if (!$tx) {
            throw new RuntimeException('Transaksi tidak ditemukan.');
        }
        if ($tx['status'] === 'DIBATALKAN') {
            throw new RuntimeException('Transaksi sudah dibatalkan sebelumnya.');
        }

        $this->pdo->beginTransaction();
        try {
            // Tandai transaksi
            $this->execute(
                'UPDATE transactions SET status="DIBATALKAN", alasan_batal = ?, cancelled_by = ?, cancelled_at = NOW() WHERE id = ?',
                [$alasan, $this->uid(), $txId]
            );

            // Batalkan kas terkait
            $this->execute(
                'UPDATE cash_transactions SET status="DIBATALKAN" WHERE related_type="transactions" AND related_id = ? AND status="AKTIF"',
                [$txId]
            );

            // Batalkan piutang/hutang bila ada
            $this->execute(
                'UPDATE receivables SET status="DIBATALKAN" WHERE transaction_id = ? AND status="AKTIF"',
                [$txId]
            );
            $this->execute(
                'UPDATE payables SET status="DIBATALKAN" WHERE transaction_id = ? AND status="AKTIF"',
                [$txId]
            );

            // Kembalikan stok: tandai mutasi stok transaksi sebagai dibatalkan
            $details = $this->all('SELECT * FROM transaction_details WHERE transaction_id = ?', [$txId]);
            $this->execute(
                'UPDATE stock_movements SET status="DIBATALKAN" WHERE no_referensi = ? AND status="AKTIF"',
                [$tx['no_transaksi']]
            );
            foreach ($details as $d) {
                $this->recalcStok((int)$d['product_id']);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Batalkan pembayaran piutang. */
    public function cancelBayarPiutang(int $payId, string $alasan): void
    {
        if (trim($alasan) === '') {
            throw new RuntimeException('Alasan pembatalan wajib diisi.');
        }
        $pay = $this->one('SELECT * FROM receivable_payments WHERE id = ?', [$payId]);
        if (!$pay || $pay['status'] === 'DIBATALKAN') {
            throw new RuntimeException('Pembayaran tidak ditemukan atau sudah dibatalkan.');
        }
        $this->pdo->beginTransaction();
        try {
            $this->execute('UPDATE receivable_payments SET status="DIBATALKAN" WHERE id = ?', [$payId]);
            $this->execute(
                'UPDATE cash_transactions SET status="DIBATALKAN" WHERE related_type="receivable_payments" AND related_id = ? AND status="AKTIF"',
                [$payId]
            );
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Batalkan pembayaran hutang. */
    public function cancelBayarHutang(int $payId, string $alasan): void
    {
        if (trim($alasan) === '') {
            throw new RuntimeException('Alasan pembatalan wajib diisi.');
        }
        $pay = $this->one('SELECT * FROM payable_payments WHERE id = ?', [$payId]);
        if (!$pay || $pay['status'] === 'DIBATALKAN') {
            throw new RuntimeException('Pembayaran tidak ditemukan atau sudah dibatalkan.');
        }
        $this->pdo->beginTransaction();
        try {
            $this->execute('UPDATE payable_payments SET status="DIBATALKAN" WHERE id = ?', [$payId]);
            $this->execute(
                'UPDATE cash_transactions SET status="DIBATALKAN" WHERE related_type="payable_payments" AND related_id = ? AND status="AKTIF"',
                [$payId]
            );
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
