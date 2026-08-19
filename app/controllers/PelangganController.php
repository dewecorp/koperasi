<?php

require_once APP_ROOT . '/app/core/Controller.php';

class PelangganController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara', 'Petugas']);
        $pdo = db();
        $q = trim(input('q', ''));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where = '(name LIKE ? OR phone LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }
        $pg = paginate_data(
            'SELECT COUNT(*) FROM customers WHERE ' . $where,
            'SELECT * FROM customers WHERE ' . $where,
            $params,
            'ORDER BY name ASC',
            20
        );
        $this->render('ref/index', [
            'pageTitle' => 'Data Pelanggan',
            'pg' => $pg,
            'q' => $q,
            'refLabel' => 'pelanggan',
            'fields' => ['name' => 'Nama', 'phone' => 'Telepon', 'address' => 'Alamat'],
        ]);
    }

    public function store(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $this->save(null);
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $this->save($id);
    }

    private function save(?string $id): void
    {
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pelanggan');
        }
        $name = trim(input('name', ''));
        $phone = trim(input('phone', ''));
        $address = trim(input('address', ''));
        if ($name === '') {
            flash('error', 'Nama wajib diisi.');
            redirect('pelanggan');
        }
        $pdo = db();
        if ($id) {
            $pdo->prepare('UPDATE customers SET name=?, phone=?, address=? WHERE id=?')->execute([$name, $phone, $address, $id]);
            audit_log('UBAH PELANGGAN', $name);
        } else {
            $pdo->prepare('INSERT INTO customers (name, phone, address) VALUES (?, ?, ?)')->execute([$name, $phone, $address]);
            audit_log('TAMBAH PELANGGAN', $name);
        }
        flash('success', 'Data pelanggan disimpan.');
        redirect('pelanggan');
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pelanggan');
        }
        $pdo = db();
        $used = $pdo->prepare('SELECT (SELECT COUNT(*) FROM transactions WHERE customer_id = ?) + (SELECT COUNT(*) FROM receivables WHERE customer_id = ?)');
        $used->execute([$id, $id]);
        if ((int)$used->fetchColumn() > 0) {
            $pdo->prepare('UPDATE customers SET is_active = 0 WHERE id = ?')->execute([$id]);
            flash('success', 'Pelanggan dinonaktifkan karena sudah dipakai.');
        } else {
            $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
            flash('success', 'Pelanggan dihapus.');
        }
        audit_log('HAPUS PELANGGAN', 'ID: ' . $id);
        redirect('pelanggan');
    }
}
