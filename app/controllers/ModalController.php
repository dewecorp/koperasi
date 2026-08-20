<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

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
        $tahunAjaran = tahun_ajaran_aktif();

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

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $no = nomor_transaksi('MDL', $tanggal);

            // Pengurangan modal mengurangi kas; cek saldo cukup
            if ($type === 'pengurangan') {
                $fin = new FinanceService();
                $fin->checkSaldoCukup($nominal);
            }

            $pdo->prepare(
                'INSERT INTO capital_transactions (tahun_ajaran, tanggal, no_transaksi, type, nominal, keterangan, status, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, "AKTIF", ?)'
            )->execute([$tahunAjaran, $tanggal, $no, $type, $nominal, $keterangan, $_SESSION['user']['id']]);
            $capId = (int)$pdo->lastInsertId();

            // Kas: modal masuk -> buku kas masuk; pengurangan -> buku kas keluar
            $kategori = $type === 'pengurangan' ? 'Pengurangan Modal' : 'Modal';
            $jenis = $type === 'pengurangan' ? 'keluar' : 'masuk';
            $pdo->prepare(
                'INSERT INTO cash_transactions (tahun_ajaran, tanggal, no_transaksi, jenis, kategori, nominal, keterangan, status, related_type, related_id, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "AKTIF", "capital_transactions", ?, ?)'
            )->execute([$tahunAjaran, $tanggal, $no, $jenis, $kategori, $nominal, 'Modal ' . $no . ' - ' . $keterangan, $capId, $_SESSION['user']['id']]);

            $pdo->commit();
            audit_log('TAMBAH MODAL', $type . ' ' . rupiah($nominal));
            flash('success', 'Transaksi modal dicatat dan masuk ke buku kas.');
        } catch (Throwable $e) {
            $pdo->rollBack();
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
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE capital_transactions SET status = "DIBATALKAN" WHERE id = ?')->execute([$id]);
            // Balikkan efek kas
            $pdo->prepare('UPDATE cash_transactions SET status = "DIBATALKAN" WHERE related_type = "capital_transactions" AND related_id = ? AND status = "AKTIF"')->execute([$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', $e->getMessage());
            redirect('modal');
        }
        audit_log('BATAL TRANSAKSI MODAL', 'ID: ' . $id);
        flash('success', 'Transaksi modal dibatalkan. Efek kas dibalik otomatis.');
        redirect('modal');
    }
}