<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class HutangController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $q = trim(input('q', ''));
        $status = input('status', '');

        $where = ['p.status = "AKTIF"'];
        $params = [];
        if ($q !== '') {
            $where[] = '(s.name LIKE ? OR p.no_transaksi LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like]);
        }
        $whereSql = implode(' AND ', $where);

        $dataSql = 'SELECT p.*, s.name AS supplier,
                    COALESCE((SELECT SUM(pm.nominal) FROM payable_payments pm WHERE pm.payable_id = p.id AND pm.status="AKTIF"),0) AS dibayar,
                    (p.total - COALESCE((SELECT SUM(pm.nominal) FROM payable_payments pm WHERE pm.payable_id = p.id AND pm.status="AKTIF"),0)) AS sisa
                    FROM payables p JOIN suppliers s ON s.id = p.supplier_id
                    WHERE ' . $whereSql;
        $pg = paginate_data(
            'SELECT COUNT(*) FROM payables p JOIN suppliers s ON s.id = p.supplier_id WHERE ' . $whereSql,
            $dataSql,
            $params,
            'ORDER BY p.id DESC',
            20
        );

        if ($status === 'lunas') {
            $pg['items'] = array_values(array_filter($pg['items'], fn($r) => (float)$r['sisa'] <= 0));
        } elseif ($status === 'sebagian') {
            $pg['items'] = array_values(array_filter($pg['items'], fn($r) => (float)$r['dibayar'] > 0 && (float)$r['sisa'] > 0));
        } elseif ($status === 'belum') {
            $pg['items'] = array_values(array_filter($pg['items'], fn($r) => (float)$r['dibayar'] <= 0 && (float)$r['sisa'] > 0));
        }

        $this->render('hutang/index', [
            'pageTitle' => 'Hutang',
            'pg' => $pg,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function show(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $hutang = $this->load($id);
        $fin = new FinanceService();
        $sisa = $fin->hutangSisa((int)$id);

        $stmt = $pdo->prepare(
            'SELECT hp.*, u.username FROM payable_payments hp LEFT JOIN users u ON u.id = hp.user_id WHERE hp.payable_id = ? ORDER BY hp.tanggal DESC, hp.id DESC'
        );
        $stmt->execute([$id]);
        $payments = $stmt->fetchAll();

        $this->render('hutang/show', [
            'pageTitle' => 'Detail Hutang',
            'hutang' => $hutang,
            'sisa' => $sisa,
            'payments' => $payments,
        ]);
    }

    public function bayar(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('hutang&action=show&id=' . $id);
        }
        $hutang = $this->load($id);
        $tanggal = input('tanggal', date('Y-m-d'));
        $nominal = (float)input('nominal', 0);
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date', 'nominal' => 'numeric|min:1']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('hutang&action=show&id=' . $id);
        }

        try {
            (new FinanceService())->bayarHutang((int)$id, $tanggal, $nominal, $keterangan);
            audit_log('BAYAR HUTANG', $hutang['no_transaksi'] . ' ' . rupiah($nominal));
            flash('success', 'Pembayaran hutang dicatat. Kas berkurang.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('hutang&action=show&id=' . $id);
    }

    public function cancel_bayar(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('hutang');
        }
        $pay = db()->prepare('SELECT * FROM payable_payments WHERE id = ?');
        $pay->execute([$id]);
        $row = $pay->fetch() ?: null;
        if (!$row) {
            abort_notfound('Pembayaran tidak ditemukan.');
        }
        $alasan = trim(input('alasan', ''));
        try {
            (new FinanceService())->cancelBayarHutang((int)$id, $alasan);
            audit_log('BATAL BAYAR HUTANG', $row['no_bukti'] . ' - ' . $alasan);
            flash('success', 'Pembayaran dibatalkan. Kas telah dibalik.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('hutang&action=show&id=' . $row['payable_id']);
    }

    public function edit(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $hutang = $this->load($id);
        $this->render('hutang/edit', [
            'pageTitle' => 'Ubah Hutang',
            'hutang' => $hutang,
        ]);
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('hutang&action=show&id=' . $id);
        }
        $hutang = $this->load($id);
        $jatuhTempo = input('jatuh_tempo', '');
        $errors = validate(['jatuh_tempo' => 'date']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('hutang&action=edit&id=' . $id);
        }
        db()->prepare('UPDATE payables SET jatuh_tempo = ? WHERE id = ?')->execute([$jatuhTempo ?: null, $id]);
        audit_log('UBAH HUTANG', $hutang['no_transaksi'] . ' jatuh tempo: ' . $jatuhTempo);
        flash('success', 'Hutang diperbarui.');
        redirect('hutang&action=show&id=' . $id);
    }

    /** Hapus hutang = batalkan pembelian kredit terkait (soft, admin). */
    public function cancel(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('hutang');
        }
        $hutang = $this->load($id);
        $alasan = trim(input('alasan', ''));
        if (!$hutang['transaction_id']) {
            flash('error', 'Hutang tidak terhubung ke pembelian.');
            redirect('hutang');
        }
        try {
            (new FinanceService())->cancelTransaction((int)$hutang['transaction_id'], $alasan);
            audit_log('HAPUS HUTANG', $hutang['no_transaksi'] . ' - ' . $alasan);
            flash('success', 'Hutang dihapus (pembelian dibatalkan, stok & kas dibalik).');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('hutang');
    }

    private function load(?string $id): array
    {
        $stmt = db()->prepare(
            'SELECT p.*, s.name AS supplier, t.no_transaksi AS no_pembelian
             FROM payables p
             JOIN suppliers s ON s.id = p.supplier_id
             LEFT JOIN transactions t ON t.id = p.transaction_id
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch() ?: null;
        if (!$row) {
            abort_notfound('Hutang tidak ditemukan.');
        }
        return $row;
    }
}