<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class PengeluaranController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();

        $dari = input('dari', date('Y-m-01'));
        $sampai = input('sampai', date('Y-m-d'));
        $q = trim(input('q', ''));

        $where = ['t.type = "pengeluaran"', 't.tanggal BETWEEN ? AND ?'];
        $params = [$dari, $sampai];
        if ($q !== '') {
            $where[] = '(t.no_transaksi LIKE ? OR t.keterangan LIKE ? OR c.name LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        $whereSql = implode(' AND ', $where);

        $pg = paginate_data(
            'SELECT COUNT(*) FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE ' . $whereSql,
            'SELECT t.*, c.name AS kategori, u.username, ct.kategori AS penerima
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             JOIN users u ON u.id = t.user_id
             LEFT JOIN cash_transactions ct ON ct.related_type = "transactions" AND ct.related_id = t.id AND ct.status = "AKTIF"
             WHERE ' . $whereSql,
            $params,
            'ORDER BY t.id DESC',
            20
        );

        $this->render('pengeluaran/index', [
            'pageTitle' => 'Pengeluaran',
            'pg' => $pg,
            'dari' => $dari,
            'sampai' => $sampai,
            'q' => $q,
            'kategori' => $pdo->query('SELECT * FROM categories WHERE type="pengeluaran" ORDER BY name')->fetchAll(),
            'sumberLabel' => 'Penerima',
        ]);
    }

    public function store(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pengeluaran');
        }

        $tanggal = input('tanggal', date('Y-m-d'));
        $kategoriId = (int)input('category_id', 0);
        $nominal = (float)input('nominal', 0);
        $penerima = trim(input('penerima', ''));
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date', 'nominal' => 'numeric|min:1']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            flash_old($_POST);
            redirect('pengeluaran');
        }
        if ($kategoriId < 1) {
            flash('error', 'Kategori pengeluaran wajib dipilih.');
            flash_old($_POST);
            redirect('pengeluaran');
        }

        $fin = new FinanceService();
        try {
            $txId = $fin->savePengeluaran($tanggal, $kategoriId, $nominal, $penerima, $keterangan);
            $att = save_attachment('transactions', $txId);
            if ($att['error']) {
                flash('warning', 'Transaksi tersimpan, tetapi bukti gagal: ' . $att['error']);
            }
            audit_log('TAMBAH PENGELUARAN', 'nominal: ' . rupiah($nominal));
            flash('success', 'Pengeluaran dicatat. Kas berkurang.');
            redirect('pengeluaran&action=show&id=' . $txId);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            flash_old($_POST);
            redirect('pengeluaran');
        }
    }

    public function show(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $tx = $this->load($id);
        $attachments = attachments_of('transactions', $tx['id']);
        $this->render('pengeluaran/show', [
            'pageTitle' => 'Detail Pengeluaran',
            'tx' => $tx,
            'attachments' => $attachments,
        ]);
    }

    public function upload(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pengeluaran&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        $att = save_attachment('transactions', $tx['id']);
        flash($att['error'] ? 'error' : 'success', $att['error'] ?: 'Bukti diunggah.');
        redirect('pengeluaran&action=show&id=' . $id);
    }

    public function cancel(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pengeluaran&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        $alasan = trim(input('alasan', ''));
        try {
            (new FinanceService())->cancelTransaction((int)$id, $alasan);
            audit_log('BATAL PENGELUARAN', $tx['no_transaksi'] . ' - ' . $alasan);
            flash('success', 'Pengeluaran dibatalkan. Kas telah dibalik.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('pengeluaran&action=show&id=' . $id);
    }

    public function edit(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $tx = $this->load($id);
        $this->render('pengeluaran/edit', [
            'pageTitle' => 'Ubah Pengeluaran',
            'tx' => $tx,
            'kategori' => db()->query('SELECT * FROM categories WHERE type="pengeluaran" ORDER BY name')->fetchAll(),
        ]);
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pengeluaran&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        if ($tx['status'] === 'DIBATALKAN') {
            flash('error', 'Transaksi yang dibatalkan tidak dapat diubah.');
            redirect('pengeluaran&action=show&id=' . $id);
        }

        $tanggal = input('tanggal', $tx['tanggal']);
        $kategoriId = (int)input('category_id', $tx['category_id']);
        $nominal = (float)input('nominal', $tx['total']);
        $penerima = trim(input('penerima', ''));
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date', 'nominal' => 'numeric|min:1']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('pengeluaran&action=edit&id=' . $id);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE transactions SET tanggal = ?, category_id = ?, total = ?, keterangan = ? WHERE id = ?')
                ->execute([$tanggal, $kategoriId, $nominal, $keterangan, $id]);
            $pdo->prepare('UPDATE cash_transactions SET tanggal = ?, kategori = ?, nominal = ?, keterangan = ? WHERE related_type = "transactions" AND related_id = ? AND status = "AKTIF"')
                ->execute([$tanggal, $penerima, $nominal, $keterangan, $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal mengubah: ' . $e->getMessage());
            redirect('pengeluaran&action=edit&id=' . $id);
        }

        audit_log('UBAH PENGELUARAN', $tx['no_transaksi']);
        flash('success', 'Pengeluaran diperbarui.');
        redirect('pengeluaran&action=show&id=' . $id);
    }

    private function load(?string $id): array
    {
        $stmt = db()->prepare(
            'SELECT t.*, c.name AS kategori, u.username AS user_name
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             JOIN users u ON u.id = t.user_id
             WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        $tx = $stmt->fetch() ?: null;
        if (!$tx) {
            abort_notfound('Data pengeluaran tidak ditemukan.');
        }
        return $tx;
    }
}