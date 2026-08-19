<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class PiutangController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $tahunAjaran = tahun_ajaran_aktif();
        $q = trim(input('q', ''));
        $status = input('status', '');

        $where = ['r.status = "AKTIF"', 'r.tahun_ajaran = ?'];
        $params = [$tahunAjaran];
        if ($q !== '') {
            $where[] = '(c.name LIKE ? OR r.no_transaksi LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like]);
        }
        $whereSql = implode(' AND ', $where);

        $dataSql = 'SELECT r.*, c.name AS pelanggan,
                    COALESCE((SELECT SUM(p.nominal) FROM receivable_payments p WHERE p.receivable_id = r.id AND p.status="AKTIF"),0) AS dibayar,
                    (r.total - COALESCE((SELECT SUM(p.nominal) FROM receivable_payments p WHERE p.receivable_id = r.id AND p.status="AKTIF"),0)) AS sisa
                    FROM receivables r JOIN customers c ON c.id = r.customer_id
                    WHERE ' . $whereSql;
        $pg = paginate_data(
            'SELECT COUNT(*) FROM receivables r JOIN customers c ON c.id = r.customer_id WHERE ' . $whereSql,
            $dataSql,
            $params,
            'ORDER BY r.id DESC',
            20
        );

        // Filter status di memori (sisa > 0, dsb)
        if ($status === 'lunas') {
            $pg['items'] = array_values(array_filter($pg['items'], fn($r) => (float)$r['sisa'] <= 0));
        } elseif ($status === 'sebagian') {
            $pg['items'] = array_values(array_filter($pg['items'], fn($r) => (float)$r['dibayar'] > 0 && (float)$r['sisa'] > 0));
        } elseif ($status === 'belum') {
            $pg['items'] = array_values(array_filter($pg['items'], fn($r) => (float)$r['dibayar'] <= 0 && (float)$r['sisa'] > 0));
        }

        $this->render('piutang/index', [
            'pageTitle' => 'Piutang',
            'pg' => $pg,
            'tahunAjaran' => $tahunAjaran,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function show(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $piutang = $this->load($id);
        $fin = new FinanceService();
        $sisa = $fin->piutangSisa((int)$id);

        $stmt = $pdo->prepare(
            'SELECT rp.*, u.username FROM receivable_payments rp LEFT JOIN users u ON u.id = rp.user_id WHERE rp.receivable_id = ? ORDER BY rp.tanggal DESC, rp.id DESC'
        );
        $stmt->execute([$id]);
        $payments = $stmt->fetchAll();

        $this->render('piutang/show', [
            'pageTitle' => 'Detail Piutang',
            'piutang' => $piutang,
            'sisa' => $sisa,
            'payments' => $payments,
        ]);
    }

    public function bayar(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('piutang&action=show&id=' . $id);
        }
        $piutang = $this->load($id);
        $tahunAjaran = input('tahun_ajaran', tahun_ajaran_aktif());
        $tanggal = input('tanggal', date('Y-m-d'));
        $nominal = (float)input('nominal', 0);
        $keterangan = trim(input('keterangan', ''));

        $errors = validate(['tanggal' => 'date', 'nominal' => 'numeric|min:1']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('piutang&action=show&id=' . $id);
        }

        try {
            (new FinanceService())->bayarPiutang((int)$id, $tanggal, $nominal, $keterangan, $tahunAjaran);
            audit_log('BAYAR PIUTANG', $piutang['no_transaksi'] . ' ' . rupiah($nominal));
            flash('success', 'Pembayaran piutang dicatat. Kas bertambah.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('piutang&action=show&id=' . $id);
    }

    public function cancel_bayar(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('piutang');
        }
        $pay = db()->prepare('SELECT * FROM receivable_payments WHERE id = ?');
        $pay->execute([$id]);
        $row = $pay->fetch() ?: null;
        if (!$row) {
            abort_notfound('Pembayaran tidak ditemukan.');
        }
        $alasan = trim(input('alasan', ''));
        try {
            (new FinanceService())->cancelBayarPiutang((int)$id, $alasan);
            audit_log('BATAL BAYAR PIUTANG', $row['no_bukti'] . ' - ' . $alasan);
            flash('success', 'Pembayaran dibatalkan. Kas telah dibalik.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('piutang&action=show&id=' . $row['receivable_id']);
    }

    public function edit(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $piutang = $this->load($id);
        $this->render('piutang/edit', [
            'pageTitle' => 'Ubah Piutang',
            'piutang' => $piutang,
        ]);
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('piutang&action=show&id=' . $id);
        }
        $piutang = $this->load($id);
        $jatuhTempo = input('jatuh_tempo', '');
        $errors = validate(['jatuh_tempo' => 'date']);
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('piutang&action=edit&id=' . $id);
        }
        db()->prepare('UPDATE receivables SET jatuh_tempo = ? WHERE id = ?')->execute([$jatuhTempo ?: null, $id]);
        audit_log('UBAH PIUTANG', $piutang['no_transaksi'] . ' jatuh tempo: ' . $jatuhTempo);
        flash('success', 'Piutang diperbarui.');
        redirect('piutang&action=show&id=' . $id);
    }

    /** Hapus piutang = batalkan penjualan kredit terkait (soft, admin). */
    public function cancel(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('piutang');
        }
        $piutang = $this->load($id);
        $alasan = trim(input('alasan', ''));
        if (!$piutang['transaction_id']) {
            flash('error', 'Piutang tidak terhubung ke penjualan.');
            redirect('piutang');
        }
        try {
            (new FinanceService())->cancelTransaction((int)$piutang['transaction_id'], $alasan);
            audit_log('HAPUS PIUTANG', $piutang['no_transaksi'] . ' - ' . $alasan);
            flash('success', 'Piutang dihapus (penjualan dibatalkan, stok & kas dibalik).');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('piutang');
    }

    private function load(?string $id): array
    {
        $stmt = db()->prepare(
            'SELECT r.*, c.name AS pelanggan, c.phone AS telp, c.address AS alamat, t.no_transaksi AS no_penjualan
             FROM receivables r
             JOIN customers c ON c.id = r.customer_id
             LEFT JOIN transactions t ON t.id = r.transaction_id
             WHERE r.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch() ?: null;
        if (!$row) {
            abort_notfound('Piutang tidak ditemukan.');
        }
        return $row;
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('piutang');
        }
        $piutang = $this->load($id);
        $noTransaksi = $piutang['no_transaksi'];

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Hapus pembayaran piutang
            $pdo->prepare('DELETE FROM receivable_payments WHERE receivable_id = ?')->execute([$id]);
            // Hapus piutang
            $pdo->prepare('DELETE FROM receivables WHERE id = ?')->execute([$id]);
            $pdo->commit();
            audit_log('HAPUS PIUTANG', $noTransaksi);
            flash('success', 'Piutang berhasil dihapus.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal menghapus: ' . $e->getMessage());
        }
        redirect('piutang');
    }
}