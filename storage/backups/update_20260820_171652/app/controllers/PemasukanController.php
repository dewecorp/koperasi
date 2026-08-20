<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class PemasukanController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();

        $tahunAjaran = tahun_ajaran_aktif();
        $dari = input('dari', date('Y-m-01'));
        $sampai = input('sampai', date('Y-m-d'));
        $q = trim(input('q', ''));

        $where = ['t.type = "pemasukan"', 't.tahun_ajaran = ?', 't.tanggal BETWEEN ? AND ?'];
        $params = [$tahunAjaran, $dari, $sampai];
        if ($q !== '') {
            $where[] = '(t.no_transaksi LIKE ? OR t.keterangan LIKE ? OR c.name LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        $whereSql = implode(' AND ', $where);

        $pg = paginate_data(
            'SELECT COUNT(*) FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE ' . $whereSql,
            'SELECT t.*, c.name AS kategori, u.username, ct.kategori AS sumber
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             JOIN users u ON u.id = t.user_id
             LEFT JOIN cash_transactions ct ON ct.related_type = "transactions" AND ct.related_id = t.id AND ct.status = "AKTIF"
             WHERE ' . $whereSql,
            $params,
            'ORDER BY t.id DESC',
            20
        );

        $this->render('pemasukan/index', [
            'pageTitle' => 'Pemasukan Lain',
            'pg' => $pg,
            'tahunAjaran' => $tahunAjaran,
            'dari' => $dari,
            'sampai' => $sampai,
            'q' => $q,
            'kategori' => $pdo->query('SELECT * FROM categories WHERE type="pemasukan" ORDER BY name')->fetchAll(),
            'sumberLabel' => 'Sumber',
        ]);
    }

    public function store(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pemasukan');
        }

        $tahunAjaran = input('tahun_ajaran', tahun_ajaran_aktif());
        $tanggal = input('tanggal', date('Y-m-d'));
        $kategoriId = (int)input('category_id', 0);
        $nominal = (float)input('nominal', 0);
        $sumber = trim(input('sumber', ''));
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date', 'nominal' => 'numeric|min:1']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            flash_old($_POST);
            redirect('pemasukan');
        }
        if ($kategoriId < 1) {
            flash('error', 'Kategori pemasukan wajib dipilih.');
            flash_old($_POST);
            redirect('pemasukan');
        }

        $fin = new FinanceService();
        try {
            $txId = $fin->savePemasukan($tanggal, $kategoriId, $nominal, $sumber, $keterangan, $tahunAjaran);
            $att = save_attachment('transactions', $txId);
            if ($att['error']) {
                flash('warning', 'Transaksi tersimpan, tetapi bukti gagal: ' . $att['error']);
            }
            audit_log('TAMBAH PEMASUKAN', 'nominal: ' . rupiah($nominal));
            flash('success', 'Pemasukan dicatat. Kas bertambah.');
            redirect('pemasukan&action=show&id=' . $txId);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            flash_old($_POST);
            redirect('pemasukan');
        }
    }

    public function show(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $tx = $this->load($id);
        $attachments = attachments_of('transactions', $tx['id']);
        $this->render('pemasukan/show', [
            'pageTitle' => 'Detail Pemasukan',
            'tx' => $tx,
            'attachments' => $attachments,
        ]);
    }

    public function upload(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pemasukan&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        $att = save_attachment('transactions', $tx['id']);
        flash($att['error'] ? 'error' : 'success', $att['error'] ?: 'Bukti diunggah.');
        redirect('pemasukan&action=show&id=' . $id);
    }

    public function cancel(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pemasukan&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        $alasan = trim(input('alasan', ''));
        try {
            (new FinanceService())->cancelTransaction((int)$id, $alasan);
            audit_log('BATAL PEMASUKAN', $tx['no_transaksi'] . ' - ' . $alasan);
            flash('success', 'Pemasukan dibatalkan. Kas telah dibalik.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('pemasukan&action=show&id=' . $id);
    }

    public function edit(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $tx = $this->load($id);
        $this->render('pemasukan/edit', [
            'pageTitle' => 'Ubah Pemasukan',
            'tx' => $tx,
            'kategori' => db()->query('SELECT * FROM categories WHERE type="pemasukan" ORDER BY name')->fetchAll(),
        ]);
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pemasukan&action=show&id=' . $id);
        }
        $tx = $this->load($id);
        if ($tx['status'] === 'DIBATALKAN') {
            flash('error', 'Transaksi yang dibatalkan tidak dapat diubah.');
            redirect('pemasukan&action=show&id=' . $id);
        }

        $tanggal = input('tanggal', $tx['tanggal']);
        $kategoriId = (int)input('category_id', $tx['category_id']);
        $nominal = (float)input('nominal', $tx['total']);
        $sumber = trim(input('sumber', ''));
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date', 'nominal' => 'numeric|min:1']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('pemasukan&action=edit&id=' . $id);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE transactions SET tanggal = ?, category_id = ?, total = ?, keterangan = ? WHERE id = ?')
                ->execute([$tanggal, $kategoriId, $nominal, $keterangan, $id]);
            $pdo->prepare('UPDATE cash_transactions SET tanggal = ?, kategori = ?, nominal = ?, keterangan = ? WHERE related_type = "transactions" AND related_id = ? AND status = "AKTIF"')
                ->execute([$tanggal, $sumber, $nominal, $keterangan, $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal mengubah: ' . $e->getMessage());
            redirect('pemasukan&action=edit&id=' . $id);
        }

        audit_log('UBAH PEMASUKAN', $tx['no_transaksi']);
        flash('success', 'Pemasukan diperbarui.');
        redirect('pemasukan&action=show&id=' . $id);
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
            abort_notfound('Data pemasukan tidak ditemukan.');
        }
        return $tx;
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pemasukan');
        }
        $tx = $this->load($id);
        $noTransaksi = $tx['no_transaksi'];

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM attachments WHERE related_type = "transactions" AND related_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM cash_transactions WHERE related_type = "transactions" AND related_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM transactions WHERE id = ?')->execute([$id]);
            $pdo->commit();
            audit_log('HAPUS PEMASUKAN', $noTransaksi);
            flash('success', 'Pemasukan berhasil dihapus.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal menghapus: ' . $e->getMessage());
        }
        redirect('pemasukan');
    }
}