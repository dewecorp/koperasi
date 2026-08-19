<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class PenjualanController extends Controller
{
    public function index(?bool $isHistory = false): void
    {
        $this->guard();
        $pdo = db();

        $dari = input('dari', date('Y-m-01'));
        $sampai = input('sampai', date('Y-m-d'));
        $q = trim(input('q', ''));
        $status = input('status', $isHistory ? 'all' : 'AKTIF');

        $where = ['t.type = "penjualan"', 't.tanggal BETWEEN ? AND ?'];
        $params = [$dari, $sampai];
        if ($q !== '') {
            $where[] = '(t.no_transaksi LIKE ? OR t.keterangan LIKE ? OR c.name LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($status === 'AKTIF') {
            $where[] = 't.status = "AKTIF"';
        } elseif ($status === 'DIBATALKAN') {
            $where[] = 't.status = "DIBATALKAN"';
        }
        $whereSql = implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM transactions t LEFT JOIN customers c ON c.id = t.customer_id WHERE ' . $whereSql;
        $dataSql = 'SELECT t.*, c.name AS pelanggan, u.username
                    FROM transactions t
                    LEFT JOIN customers c ON c.id = t.customer_id
                    JOIN users u ON u.id = t.user_id
                    WHERE ' . $whereSql;
        $pg = paginate_data($countSql, $dataSql, $params, 'ORDER BY t.id DESC', 20);

        $this->render('penjualan/index', [
            'pageTitle' => $isHistory ? 'Riwayat Penjualan' : 'Penjualan',
            'pg' => $pg,
            'dari' => $dari,
            'sampai' => $sampai,
            'q' => $q,
            'status' => $status,
            'isHistory' => $isHistory,
        ]);
    }

    /** Riwayat penjualan: semua status (termasuk dibatalkan). */
    public function history(): void
    {
        $this->index(true);
    }

    public function create(): void
    {
        $this->guard(['Administrator', 'Bendahara', 'Petugas']);
        $pdo = db();
        $this->render('penjualan/form', [
            'pageTitle' => 'Transaksi Penjualan',
            'produk' => $pdo->query(
                'SELECT p.*, c.name AS kategori FROM products p LEFT JOIN categories c ON c.id = p.category_id
                 WHERE p.is_active = 1 ORDER BY p.name'
            )->fetchAll(),
            'pelanggan' => $pdo->query('SELECT * FROM customers WHERE is_active = 1 ORDER BY name')->fetchAll(),
        ]);
    }

    public function store(): void
    {
        $this->guard(['Administrator', 'Bendahara', 'Petugas']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('penjualan&action=create');
        }

        $tanggal = input('tanggal', date('Y-m-d'));
        $metode = input('metode', 'tunai');
        $customerId = input('customer_id', null) ?: null;
        $keterangan = trim(input('keterangan', ''));
        $diskonGlobal = (float)input('diskon_global', 0);

        $errors = validate(['tanggal' => 'date', 'diskon_global' => 'numeric|min:0']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('penjualan&action=create');
        }
        if ($metode === 'kredit' && !$customerId) {
            flash('error', 'Untuk penjualan kredit, pelanggan wajib dipilih.');
            redirect('penjualan&action=create');
        }

        // Bangun item dari POST (array)
        $productIds = input('product_id', []);
        $qtys = input('qty', []);
        $hargas = input('harga', []);
        $diskons = input('diskon', []);

        $items = [];
        foreach ($productIds as $i => $pid) {
            $qty = (float)($qtys[$i] ?? 0);
            $harga = (float)($hargas[$i] ?? 0);
            $diskon = (float)($diskons[$i] ?? 0);
            if ($qty <= 0 || $harga <= 0 || !$pid) {
                continue;
            }
            $subtotal = max(0, $qty * $harga - $diskon);
            $items[] = ['product_id' => $pid, 'qty' => $qty, 'harga' => $harga, 'diskon' => $diskon, 'subtotal' => $subtotal];
        }

        if (empty($items)) {
            flash('error', 'Minimal satu barang dengan jumlah dan harga valid.');
            flash_old($_POST);
            redirect('penjualan&action=create');
        }

        $fin = new FinanceService();
        try {
            $txId = $fin->savePenjualan($tanggal, $customerId, $metode, $items, $keterangan);
            audit_log('TAMBAH PENJUALAN', 'total: ' . rupiah(array_sum(array_column($items, 'subtotal'))));
            flash('success', 'Penjualan berhasil dicatat.');
            redirect('penjualan&action=show&id=' . $txId);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            flash_old($_POST);
            redirect('penjualan&action=create');
        }
    }

    public function show(?string $id = null): void
    {
        $this->guard();
        $pdo = db();
        $tx = $this->load($id);
        $details = $pdo->prepare(
            'SELECT td.*, p.kode, p.name AS nama_barang, p.satuan FROM transaction_details td
             JOIN products p ON p.id = td.product_id WHERE td.transaction_id = ?'
        );
        $details->execute([$id]);
        $attachments = attachments_of('transactions', $tx['id']);

        $this->render('penjualan/show', [
            'pageTitle' => 'Detail Penjualan',
            'tx' => $tx,
            'details' => $details->fetchAll(),
            'attachments' => $attachments,
        ]);
    }

    public function edit(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $tx = $this->load($id);
        $this->render('penjualan/edit', [
            'pageTitle' => 'Ubah Penjualan',
            'tx' => $tx,
        ]);
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('penjualan&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        if ($tx['status'] === 'DIBATALKAN') {
            flash('error', 'Transaksi yang dibatalkan tidak dapat diubah.');
            redirect('penjualan&action=show&id=' . $id);
        }

        $tanggal = input('tanggal', $tx['tanggal']);
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('penjualan&action=edit&id=' . $id);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE transactions SET tanggal = ?, keterangan = ? WHERE id = ?')
                ->execute([$tanggal, $keterangan, $id]);
            // Perbarui tanggal pada kas & piutang terkait agar urutannya konsisten
            $pdo->prepare('UPDATE cash_transactions SET tanggal = ? WHERE related_type = "transactions" AND related_id = ? AND status = "AKTIF"')
                ->execute([$tanggal, $id]);
            $pdo->prepare('UPDATE receivables SET tanggal = ? WHERE transaction_id = ? AND status = "AKTIF"')
                ->execute([$tanggal, $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal mengubah: ' . $e->getMessage());
            redirect('penjualan&action=edit&id=' . $id);
        }

        audit_log('UBAH PENJUALAN', $tx['no_transaksi']);
        flash('success', 'Penjualan diperbarui.');
        redirect('penjualan&action=show&id=' . $id);
    }

    public function cancel(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('penjualan&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        $alasan = trim(input('alasan', ''));
        $fin = new FinanceService();
        try {
            $fin->cancelTransaction((int)$id, $alasan);
            audit_log('BATAL PENJUALAN', $tx['no_transaksi'] . ' - ' . $alasan);
            flash('success', 'Transaksi dibatalkan. Efek kas/stok/piutang telah dibalik otomatis.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('penjualan&action=show&id=' . $id);
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('penjualan');
        }
        $tx = $this->load($id);
        $noTransaksi = $tx['no_transaksi'];

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Hapus attachment
            $pdo->prepare('DELETE FROM attachments WHERE related_type = "transactions" AND related_id = ?')->execute([$id]);

            // Hapus detail transaksi
            $pdo->prepare('DELETE FROM transaction_details WHERE transaction_id = ?')->execute([$id]);

            // Hapus mutasi stok terkait
            $pdo->prepare('DELETE FROM stock_movements WHERE no_referensi = ?')->execute([$tx['no_transaksi']]);

            // Hapus piutang dan pembayarannya
            $pdo->prepare('DELETE FROM receivable_payments WHERE receivable_id IN (SELECT id FROM receivables WHERE transaction_id = ?)')->execute([$id]);
            $pdo->prepare('DELETE FROM receivables WHERE transaction_id = ?')->execute([$id]);

            // Hapus kas transactions
            $pdo->prepare('DELETE FROM cash_transactions WHERE related_type = "transactions" AND related_id = ?')->execute([$id]);

            // Hapus audit logs terkait
            $pdo->prepare('DELETE FROM audit_logs WHERE detail LIKE ?')->execute(["%{$tx['no_transaksi']}%"]);

            // Hapus transaksi utama
            $pdo->prepare('DELETE FROM transactions WHERE id = ?')->execute([$id]);

            $pdo->commit();
            audit_log('HAPUS PENJUALAN', $noTransaksi);
            flash('success', 'Penjualan berhasil dihapus permanen.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal menghapus: ' . $e->getMessage());
        }
        redirect('penjualan');
    }

    public function upload(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara', 'Petugas']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('penjualan&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        $result = save_attachment('transactions', $tx['id']);
        if ($result['error']) {
            flash('error', $result['error']);
        } else {
            audit_log('UPLOAD BUKTI PENJUALAN', $tx['no_transaksi']);
            flash('success', 'Bukti diunggah.');
        }
        redirect('penjualan&action=show&id=' . $id);
    }

    public function delete_att(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('penjualan');
        }
        delete_attachment((int)$id, 'transactions');
        redirect('penjualan&action=show&id=' . input('tx'));
    }

    public function struk(?string $id = null): void
    {
        $this->guard();
        $tx = $this->load($id);
        $stmt = db()->prepare(
            'SELECT td.*, p.name AS nama_barang, p.satuan FROM transaction_details td
             JOIN products p ON p.id = td.product_id WHERE td.transaction_id = ?'
        );
        $stmt->execute([$id]);
        $this->renderPrint('penjualan/struk', [
            'tx' => $tx,
            'details' => $stmt->fetchAll(),
            'profile' => koperasi_profile(),
        ]);
    }

    private function load(?string $id): array
    {
        $stmt = db()->prepare(
            'SELECT t.*, c.name AS pelanggan, u.username AS user_name, r.id AS receivable_id, r.jatuh_tempo
             FROM transactions t
             LEFT JOIN customers c ON c.id = t.customer_id
             JOIN users u ON u.id = t.user_id
             LEFT JOIN receivables r ON r.transaction_id = t.id AND r.status = "AKTIF"
             WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        $tx = $stmt->fetch() ?: null;
        if (!$tx) {
            abort_notfound('Transaksi penjualan tidak ditemukan.');
        }
        return $tx;
    }
}