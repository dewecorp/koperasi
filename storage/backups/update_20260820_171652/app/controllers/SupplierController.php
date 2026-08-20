<?php

require_once APP_ROOT . '/app/core/Controller.php';

class SupplierController extends Controller
{
    private function table(): string { return 'suppliers'; }
    private function page(): string { return 'supplier'; }

    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
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
            'SELECT COUNT(*) FROM suppliers WHERE ' . $where,
            'SELECT * FROM suppliers WHERE ' . $where,
            $params,
            'ORDER BY name ASC',
            20
        );
        $this->render('ref/index', [
            'pageTitle' => 'Data Supplier',
            'pg' => $pg,
            'q' => $q,
            'refLabel' => 'supplier',
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
            redirect('supplier');
        }
        $name = trim(input('name', ''));
        $phone = trim(input('phone', ''));
        $address = trim(input('address', ''));
        if ($name === '') {
            flash('error', 'Nama wajib diisi.');
            redirect('supplier');
        }
        $pdo = db();
        if ($id) {
            $pdo->prepare('UPDATE suppliers SET name=?, phone=?, address=? WHERE id=?')->execute([$name, $phone, $address, $id]);
            audit_log('UBAH SUPPLIER', $name);
        } else {
            $pdo->prepare('INSERT INTO suppliers (name, phone, address) VALUES (?, ?, ?)')->execute([$name, $phone, $address]);
            audit_log('TAMBAH SUPPLIER', $name);
        }
        flash('success', 'Data supplier disimpan.');
        redirect('supplier');
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('supplier');
        }
        $pdo = db();
        $used = $pdo->prepare('SELECT (SELECT COUNT(*) FROM products WHERE supplier_id = ?) + (SELECT COUNT(*) FROM transactions WHERE supplier_id = ?) + (SELECT COUNT(*) FROM payables WHERE supplier_id = ?)');
        $used->execute([$id, $id, $id]);
        if ((int)$used->fetchColumn() > 0) {
            $pdo->prepare('UPDATE suppliers SET is_active = 0 WHERE id = ?')->execute([$id]);
            flash('success', 'Supplier dinonaktifkan karena sudah dipakai.');
        } else {
            $pdo->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
            flash('success', 'Supplier dihapus.');
        }
        audit_log('HAPUS SUPPLIER', 'ID: ' . $id);
        redirect('supplier');
    }
}
