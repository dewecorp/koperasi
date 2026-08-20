<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class PembelianController extends Controller
{
    public function index(?bool $isHistory = false): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();

        $tahunAjaran = tahun_ajaran_aktif();
        $dari = input('dari', date('Y-m-01'));
        $sampai = input('sampai', date('Y-m-d'));
        $q = trim(input('q', ''));
        $status = input('status', $isHistory ? 'all' : 'AKTIF');

        $where = ['t.type = "pembelian"', 't.tahun_ajaran = ?', 't.tanggal BETWEEN ? AND ?'];
        $params = [$tahunAjaran, $dari, $sampai];
        if ($q !== '') {
            $where[] = '(t.no_transaksi LIKE ? OR t.keterangan LIKE ? OR s.name LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($status === 'AKTIF') {
            $where[] = 't.status = "AKTIF"';
        } elseif ($status === 'DIBATALKAN') {
            $where[] = 't.status = "DIBATALKAN"';
        }
        $whereSql = implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM transactions t LEFT JOIN suppliers s ON s.id = t.supplier_id WHERE ' . $whereSql;
        $dataSql = 'SELECT t.*, s.name AS supplier, u.username
                    FROM transactions t
                    LEFT JOIN suppliers s ON s.id = t.supplier_id
                    JOIN users u ON u.id = t.user_id
                    WHERE ' . $whereSql;
        $pg = paginate_data($countSql, $dataSql, $params, 'ORDER BY t.id DESC', 20);

        $this->render('pembelian/index', [
            'pageTitle' => $isHistory ? 'Riwayat Pembelian' : 'Pembelian',
            'pg' => $pg,
            'tahunAjaran' => $tahunAjaran,
            'dari' => $dari,
            'sampai' => $sampai,
            'q' => $q,
            'status' => $status,
            'isHistory' => $isHistory,
        ]);
    }

    /** Riwayat pembelian: semua status (termasuk dibatalkan). */
    public function history(): void
    {
        $this->index(true);
    }

    public function create(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $this->render('pembelian/form', [
            'pageTitle' => 'Transaksi Pembelian',
            'produk' => $pdo->query(
                'SELECT p.*, c.name AS kategori FROM products p LEFT JOIN categories c ON c.id = p.category_id
                 WHERE p.is_active = 1 ORDER BY p.name'
            )->fetchAll(),
            'supplier' => $pdo->query('SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name')->fetchAll(),
        ]);
    }

    public function store(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pembelian&action=create');
        }

        $tahunAjaran = input('tahun_ajaran', tahun_ajaran_aktif());
        $tanggal = input('tanggal', date('Y-m-d'));
        $metode = input('metode', 'tunai');
        $supplierId = input('supplier_id', null) ?: null;
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('pembelian&action=create');
        }
        if ($metode === 'kredit' && !$supplierId) {
            flash('error', 'Untuk pembelian kredit, supplier wajib dipilih.');
            redirect('pembelian&action=create');
        }

        $productIds = input('product_id', []);
        $qtys = input('qty', []);
        $hargas = input('harga', []);

        $items = [];
        foreach ($productIds as $i => $pid) {
            $qty = (float)($qtys[$i] ?? 0);
            $harga = (float)($hargas[$i] ?? 0);
            if ($qty <= 0 || $harga <= 0 || !$pid) {
                continue;
            }
            $items[] = ['product_id' => $pid, 'qty' => $qty, 'harga' => $harga, 'diskon' => 0, 'subtotal' => $qty * $harga];
        }

        if (empty($items)) {
            flash('error', 'Minimal satu barang dengan jumlah dan harga valid.');
            flash_old($_POST);
            redirect('pembelian&action=create');
        }

        $fin = new FinanceService();
        try {
            $txId = $fin->savePembelian($tanggal, $supplierId, $metode, $items, $keterangan, $tahunAjaran);
            audit_log('TAMBAH PEMBELIAN', 'total: ' . rupiah(array_sum(array_column($items, 'subtotal'))));
            flash('success', 'Pembelian berhasil dicatat.');
            redirect('pembelian&action=show&id=' . $txId);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            flash_old($_POST);
            redirect('pembelian&action=create');
        }
    }

    public function show(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $tx = $this->load($id);
        $details = $pdo->prepare(
            'SELECT td.*, p.kode, p.name AS nama_barang, p.satuan FROM transaction_details td
             JOIN products p ON p.id = td.product_id WHERE td.transaction_id = ?'
        );
        $details->execute([$id]);
        $attachments = attachments_of('transactions', $tx['id']);

        $this->render('pembelian/show', [
            'pageTitle' => 'Detail Pembelian',
            'tx' => $tx,
            'details' => $details->fetchAll(),
            'attachments' => $attachments,
        ]);
    }

    public function edit(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $tx = $this->load($id);
        $this->render('pembelian/edit', [
            'pageTitle' => 'Ubah Pembelian',
            'tx' => $tx,
        ]);
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pembelian&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        if ($tx['status'] === 'DIBATALKAN') {
            flash('error', 'Transaksi yang dibatalkan tidak dapat diubah.');
            redirect('pembelian&action=show&id=' . $id);
        }

        $tanggal = input('tanggal', $tx['tanggal']);
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('pembelian&action=edit&id=' . $id);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE transactions SET tanggal = ?, keterangan = ? WHERE id = ?')
                ->execute([$tanggal, $keterangan, $id]);
            $pdo->prepare('UPDATE cash_transactions SET tanggal = ? WHERE related_type = "transactions" AND related_id = ? AND status = "AKTIF"')
                ->execute([$tanggal, $id]);
            $pdo->prepare('UPDATE payables SET tanggal = ? WHERE transaction_id = ? AND status = "AKTIF"')
                ->execute([$tanggal, $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal mengubah: ' . $e->getMessage());
            redirect('pembelian&action=edit&id=' . $id);
        }

        audit_log('UBAH PEMBELIAN', $tx['no_transaksi']);
        flash('success', 'Pembelian diperbarui.');
        redirect('pembelian&action=show&id=' . $id);
    }

    public function cancel(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pembelian&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        $alasan = trim(input('alasan', ''));
        $fin = new FinanceService();
        try {
            $fin->cancelTransaction((int)$id, $alasan);
            audit_log('BATAL PEMBELIAN', $tx['no_transaksi'] . ' - ' . $alasan);
            flash('success', 'Transaksi dibatalkan. Efek kas/stok/hutang telah dibalik otomatis.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('pembelian&action=show&id=' . $id);
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pembelian');
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

            // Hapus hutang dan pembayarannya
            $pdo->prepare('DELETE FROM payable_payments WHERE payable_id IN (SELECT id FROM payables WHERE transaction_id = ?)')->execute([$id]);
            $pdo->prepare('DELETE FROM payables WHERE transaction_id = ?')->execute([$id]);

            // Hapus kas transactions
            $pdo->prepare('DELETE FROM cash_transactions WHERE related_type = "transactions" AND related_id = ?')->execute([$id]);

            // Hapus audit logs terkait
            $pdo->prepare('DELETE FROM audit_logs WHERE detail LIKE ?')->execute(["%{$tx['no_transaksi']}%"]);

            // Hapus transaksi utama
            $pdo->prepare('DELETE FROM transactions WHERE id = ?')->execute([$id]);

            $pdo->commit();
            audit_log('HAPUS PEMBELIAN', $noTransaksi);
            flash('success', 'Pembelian berhasil dihapus.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal menghapus: ' . $e->getMessage());
        }
        redirect('pembelian');
    }

    public function upload(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pembelian&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        $result = save_attachment('transactions', $tx['id']);
        if ($result['error']) {
            flash('error', $result['error']);
        } else {
            audit_log('UPLOAD BUKTI PEMBELIAN', $tx['no_transaksi']);
            flash('success', 'Bukti diunggah.');
        }
        redirect('pembelian&action=show&id=' . $id);
    }

    public function delete_att(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pembelian');
        }
        delete_attachment((int)$id, 'transactions');
        redirect('pembelian&action=show&id=' . input('tx'));
    }

    private function load(?string $id): array
    {
        $stmt = db()->prepare(
            'SELECT t.*, s.name AS supplier, u.username AS user_name
             FROM transactions t
             LEFT JOIN suppliers s ON s.id = t.supplier_id
             JOIN users u ON u.id = t.user_id
             WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        $tx = $stmt->fetch() ?: null;
        if (!$tx) {
            abort_notfound('Transaksi pembelian tidak ditemukan.');
        }
        return $tx;
    }
}
