<?php

require_once APP_ROOT . '/app/core/Controller.php';

class ModalController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $q = trim(input('q', ''));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where = '(no_transaksi LIKE ? OR keterangan LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }

        $pg = paginate_data(
            'SELECT COUNT(*) FROM capital_transactions WHERE status="AKTIF" AND ' . $where,
            'SELECT c.*, u.username FROM capital_transactions c LEFT JOIN users u ON u.id = c.user_id WHERE c.status="AKTIF" AND ' . $where,
            $params,
            'ORDER BY c.id DESC',
            20
        );

        $this->render('modal/index', [
            'pageTitle' => 'Modal Koperasi',
            'pg' => $pg,
            'q' => $q,
        ]);
    }

    public function store(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('modal');
        }

        $tanggal = input('tanggal', date('Y-m-d'));
        $type = input('type', 'tambahan');
        $nominal = (float)input('nominal', 0);
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date', 'nominal' => 'numeric|min:1']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            flash_old($_POST);
            redirect('modal');
        }
        if (!in_array($type, ['modal_awal', 'tambahan', 'pengurangan'], true)) {
            flash('error', 'Jenis transaksi modal tidak valid.');
            redirect('modal');
        }

        try {
            $no = nomor_transaksi('MDL', $tanggal);
            db()->prepare(
                'INSERT INTO capital_transactions (tanggal, no_transaksi, type, nominal, keterangan, status, user_id) VALUES (?, ?, ?, ?, ?, "AKTIF", ?)'
            )->execute([$tanggal, $no, $type, $nominal, $keterangan, $_SESSION['user']['id']]);
            audit_log('TAMBAH MODAL', $type . ' ' . rupiah($nominal));
            flash('success', 'Transaksi modal dicatat.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('modal');
    }

    public function cancel(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('modal');
        }
        db()->prepare('UPDATE capital_transactions SET status = "DIBATALKAN" WHERE id = ?')->execute([$id]);
        audit_log('BATAL TRANSAKSI MODAL', 'ID: ' . $id);
        flash('success', 'Transaksi modal dibatalkan.');
        redirect('modal');
    }
}