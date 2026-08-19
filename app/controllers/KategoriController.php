<?php

require_once APP_ROOT . '/app/core/Controller.php';

class KategoriController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $type = input('type', 'barang');

        $stmt = $pdo->prepare(
            'SELECT c.*,
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS jumlah_barang
             FROM categories c WHERE c.type = ? ORDER BY c.name'
        );
        $stmt->execute([$type]);
        $items = $stmt->fetchAll();

        $this->render('kategori/index', [
            'pageTitle' => 'Kategori',
            'items' => $items,
            'type' => $type,
            'types' => ['barang' => 'Barang', 'pemasukan' => 'Pemasukan', 'pengeluaran' => 'Pengeluaran'],
        ]);
    }

    public function store(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('kategori');
        }
        $name = trim(input('name', ''));
        $type = input('type', 'barang');
        if ($name === '') {
            flash('error', 'Nama kategori wajib diisi.');
            redirect('kategori');
        }
        try {
            db()->prepare('INSERT INTO categories (name, type) VALUES (?, ?)')->execute([$name, $type]);
            audit_log('TAMBAH KATEGORI', $type . ': ' . $name);
            flash('success', 'Kategori ditambahkan.');
        } catch (PDOException $e) {
            flash('error', 'Kategori "' . $name . '" sudah ada untuk tipe tersebut.');
        }
        redirect('kategori&type=' . $type);
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('kategori');
        }
        $name = trim(input('name', ''));
        $type = input('type', 'barang');
        if ($name === '') {
            flash('error', 'Nama kategori wajib diisi.');
            redirect('kategori');
        }
        try {
            db()->prepare('UPDATE categories SET name = ?, type = ? WHERE id = ?')->execute([$name, $type, $id]);
            audit_log('UBAH KATEGORI', $type . ': ' . $name);
            flash('success', 'Kategori diubah.');
        } catch (PDOException $e) {
            flash('error', 'Nama kategori sudah ada untuk tipe tersebut.');
        }
        redirect('kategori&type=' . $type);
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('kategori');
        }
        $pdo = db();
        $used = $pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM products WHERE category_id = ?) + (SELECT COUNT(*) FROM transactions WHERE category_id = ?)'
        );
        $used->execute([$id, $id]);
        if ((int)$used->fetchColumn() > 0) {
            flash('error', 'Kategori masih dipakai oleh barang atau transaksi. Tidak dapat dihapus.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            audit_log('HAPUS KATEGORI', 'ID: ' . $id);
            flash('success', 'Kategori dihapus.');
        }
        redirect('kategori');
    }
}
