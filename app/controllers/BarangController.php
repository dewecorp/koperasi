<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class BarangController extends Controller
{
    public function index(): void
    {
        $this->guard();
        $pdo = db();

        $q = trim(input('q', ''));
        $cat = input('cat', '');
        $status = input('status', '');

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.kode LIKE ? OR p.name LIKE ? OR p.barcode LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($cat !== '') {
            $where[] = 'p.category_id = ?';
            $params[] = $cat;
        }
        if ($status === 'aktif') {
            $where[] = 'p.is_active = 1';
        } elseif ($status === 'nonaktif') {
            $where[] = 'p.is_active = 0';
        }
        $whereSql = implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM products p WHERE ' . $whereSql;
        $dataSql = 'SELECT p.*, c.name AS kategori, s.name AS supplier_name
                    FROM products p
                    LEFT JOIN categories c ON c.id = p.category_id
                    LEFT JOIN suppliers s ON s.id = p.supplier_id
                    WHERE ' . $whereSql;
        $pg = paginate_data($countSql, $dataSql, $params, 'ORDER BY p.kode ASC', 20);

        $kategori = $pdo->query('SELECT * FROM categories WHERE type="barang" ORDER BY name')->fetchAll();

        $this->render('barang/index', [
            'pageTitle' => 'Data Barang',
            'pg' => $pg,
            'kategori' => $kategori,
            'q' => $q,
            'cat' => $cat,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $this->render('barang/form', [
            'pageTitle' => 'Tambah Barang',
            'barang' => null,
            'kategori' => $pdo->query('SELECT * FROM categories WHERE type="barang" ORDER BY name')->fetchAll(),
            'supplier' => $pdo->query('SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name')->fetchAll(),
        ]);
    }

    public function store(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect($id ? 'barang&action=edit&id=' . $id : 'barang');
        }

        $errors = validate([
            'kode' => 'required',
            'name' => 'required',
            'satuan' => 'required',
            'harga_beli' => 'numeric|min:0',
            'harga_jual' => 'numeric|min:0',
            'stock_minimum' => 'numeric|min:0',
        ]);
        if ($errors) {
            flash_old($_POST);
            foreach ($errors as $err) flash('error', $err);
            redirect($id ? 'barang&action=edit&id=' . $id : 'barang&action=create');
        }

        $pdo = db();
        $kode = trim(input('kode', ''));
        $name = trim(input('name', ''));
        $barcode = trim(input('barcode', ''));
        $categoryId = input('category_id', null) ?: null;
        $satuan = trim(input('satuan', 'pcs'));
        $hargaBeli = (float)input('harga_beli', 0);
        $hargaJual = (float)input('harga_jual', 0);
        $stockMin = (float)input('stock_minimum', 0);
        $supplierId = input('supplier_id', null) ?: null;
        $isActive = input('is_active', '1') === '1' ? 1 : 0;

        // Cek duplikasi kode
        $dup = $pdo->prepare('SELECT id FROM products WHERE kode = ? AND id <> ?');
        $dup->execute([$kode, $id ?? 0]);
        if ($dup->fetch()) {
            flash('error', 'Kode barang "' . $kode . '" sudah digunakan.');
            flash_old($_POST);
            redirect($id ? 'barang&action=edit&id=' . $id : 'barang&action=create');
        }

        $fin = new FinanceService();
        try {
            if ($id) {
                $pdo->prepare(
                    'UPDATE products SET kode=?, barcode=?, name=?, category_id=?, satuan=?, harga_beli=?, harga_jual=?, stock_minimum=?, supplier_id=?, is_active=? WHERE id=?'
                )->execute([$kode, $barcode, $name, $categoryId, $satuan, $hargaBeli, $hargaJual, $stockMin, $supplierId, $isActive, $id]);
                audit_log('UBAH BARANG', 'Kode: ' . $kode);
                flash('success', 'Barang berhasil diubah.');
            } else {
                $pdo->prepare(
                    'INSERT INTO products (kode, barcode, name, category_id, satuan, harga_beli, harga_jual, stock, stock_minimum, supplier_id, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
                )->execute([$kode, $barcode, $name, $categoryId, $satuan, $hargaBeli, $hargaJual, $stockMin, $supplierId, $isActive]);
                $newId = (int)$pdo->lastInsertId();

                // Stok awal manual -> penyesuaian
                $stockAwal = (float)input('stock_awal', 0);
                if ($stockAwal > 0) {
                    $pdo->prepare(
                        'INSERT INTO stock_movements (product_id, tanggal, no_referensi, type, qty, keterangan, status, user_id)
                         VALUES (?, CURDATE(), NULL, "penyesuaian", ?, "Stok awal barang", "AKTIF", ?)'
                    )->execute([$newId, $stockAwal, $_SESSION['user']['id']]);
                    $fin->recalcStok($newId);
                }
                audit_log('TAMBAH BARANG', 'Kode: ' . $kode);
                flash('success', 'Barang berhasil ditambahkan.');
            }
            redirect('barang');
        } catch (Throwable $e) {
            flash('error', 'Gagal menyimpan barang: ' . $e->getMessage());
            flash_old($_POST);
            redirect($id ? 'barang&action=edit&id=' . $id : 'barang&action=create');
        }
    }

    public function edit(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $barang = $stmt->fetch() ?: null;
        if (!$barang) {
            abort_notfound('Barang tidak ditemukan.');
        }
        $this->render('barang/form', [
            'pageTitle' => 'Ubah Barang',
            'barang' => $barang,
            'kategori' => $pdo->query('SELECT * FROM categories WHERE type="barang" ORDER BY name')->fetchAll(),
            'supplier' => $pdo->query('SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name')->fetchAll(),
        ]);
    }

    /** Hapus banyak barang sekaligus (bulk). */
    public function delete_many(): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('barang');
        }
        $ids = input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            flash('warning', 'Tidak ada barang yang dipilih.');
            redirect('barang');
        }

        $pdo = db();
        $deleted = 0;
        $deactivated = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $barang = $stmt->fetch();
            if (!$barang) {
                continue;
            }
            $used = $pdo->prepare('SELECT COUNT(*) FROM transaction_details WHERE product_id = ?');
            $used->execute([$id]);
            if ((int)$used->fetchColumn() > 0) {
                $pdo->prepare('UPDATE products SET is_active = 0 WHERE id = ?')->execute([$id]);
                $deactivated++;
            } else {
                // Barang belum pernah dipakai transaksi: boleh dihapus penuh.
                // Bersihkan mutasi stok & stok terkait dulu (FK).
                $pdo->prepare('DELETE FROM stock_movements WHERE product_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
                $deleted++;
            }
        }

        audit_log('HAPUS BANYAK BARANG', 'dihapus: ' . $deleted . ', dinonaktifkan: ' . $deactivated);
        if ($deleted > 0) {
            flash('success', $deleted . ' barang berhasil dihapus.');
        }
        if ($deactivated > 0) {
            flash('warning', $deactivated . ' barang tidak dapat dihapus karena sudah dipakai transaksi, sehingga dinonaktifkan.');
        }
        if ($deleted === 0 && $deactivated === 0) {
            flash('warning', 'Tidak ada barang yang dapat dihapus.');
        }
        redirect('barang');
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('barang');
        }
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $barang = $stmt->fetch();
        if (!$barang) {
            abort_notfound('Barang tidak ditemukan.');
        }

        $used = $pdo->prepare('SELECT COUNT(*) FROM transaction_details WHERE product_id = ?');
        $used->execute([$id]);
        if ((int)$used->fetchColumn() > 0) {
            // Barang pernah dipakai -> nonaktifkan, bukan hapus
            $pdo->prepare('UPDATE products SET is_active = 0 WHERE id = ?')->execute([$id]);
            audit_log('NONAKTIFKAN BARANG', 'Kode: ' . $barang['kode'] . ' (pernah dipakai transaksi)');
            flash('warning', 'Barang tidak dapat dihapus karena sudah dipakai dalam transaksi. Barang dinonaktifkan.');
        } else {
            // Bersihkan mutasi stok & stok terkait dulu (FK)
            $pdo->prepare('DELETE FROM stock_movements WHERE product_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            audit_log('HAPUS BARANG', 'Kode: ' . $barang['kode']);
            flash('success', 'Barang berhasil dihapus.');
        }
        redirect('barang');
    }

    public function active(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('barang');
        }
        $active = input('val', '1') === '1' ? 1 : 0;
        db()->prepare('UPDATE products SET is_active = ? WHERE id = ?')->execute([$active, $id]);
        audit_log('UBAH STATUS BARANG', 'ID: ' . $id . ' -> ' . ($active ? 'aktif' : 'nonaktif'));
        flash('success', 'Status barang diperbarui.');
        redirect('barang');
    }

    public function show(?string $id = null): void
    {
        $this->guard();
        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT p.*, c.name AS kategori, s.name AS supplier_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $barang = $stmt->fetch() ?: null;
        if (!$barang) {
            abort_notfound('Barang tidak ditemukan.');
        }

        // Kartu stok
        $movements = $pdo->prepare(
            'SELECT * FROM stock_movements WHERE product_id = ? AND status="AKTIF" ORDER BY tanggal, id'
        );
        $movements->execute([$id]);
        $movements = $movements->fetchAll();
        $saldo = 0;
        foreach ($movements as &$m) {
            if ($m['type'] === 'masuk') $saldo += (float)$m['qty'];
            if ($m['type'] === 'keluar') $saldo -= (float)$m['qty'];
            if ($m['type'] === 'penyesuaian') $saldo += (float)$m['qty'];
            $m['saldo'] = $saldo;
        }
        unset($m);

        $this->render('barang/detail', [
            'pageTitle' => 'Detail Barang',
            'barang' => $barang,
            'movements' => $movements,
        ]);
    }

    public function adjust(?string $id = null): void
    {
        $this->guard(['Administrator', 'Bendahara']);
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $barang = $stmt->fetch() ?: null;
        if (!$barang) {
            abort_notfound('Barang tidak ditemukan.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                flash('error', 'Token keamanan tidak valid.');
                redirect('barang&action=adjust&id=' . $id);
            }
            $jenis = input('jenis', 'masuk');
            $qty = (float)input('qty', 0);
            $keterangan = trim(input('keterangan', ''));
            if ($qty <= 0) {
                flash('error', 'Jumlah penyesuaian harus lebih dari 0.');
            } else {
                $fin = new FinanceService();
                try {
                    $pdo->beginTransaction();
                    $no = nomor_transaksi('STK');
                    $type = $jenis === 'keluar' ? 'keluar' : 'masuk';
                    $stok = $fin->stokProduk((int)$id);
                    if ($type === 'keluar' && $qty > $stok && setting('allow_negative_stock', '0') === '0') {
                        throw new RuntimeException('Stok tidak cukup untuk penyesuaian keluar (stok: ' . angka($stok) . ').');
                    }
                    $pdo->prepare(
                        'INSERT INTO stock_movements (product_id, tanggal, no_referensi, type, qty, keterangan, status, user_id)
                         VALUES (?, CURDATE(), ?, ?, ?, ?, "AKTIF", ?)'
                    )->execute([$id, $no, $type, $qty, 'Penyesuaian ' . ($type === 'keluar' ? 'keluar' : 'masuk') . ': ' . $keterangan, $_SESSION['user']['id']]);
                    $fin->recalcStok((int)$id);
                    $pdo->commit();
                    audit_log('PENYESUAIAN STOK', 'Barang: ' . $barang['kode'] . ', ' . $type . ' ' . angka($qty));
                    flash('success', 'Stok disesuaikan.');
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    flash('error', $e->getMessage());
                }
            }
            redirect('barang&action=show&id=' . $id);
        }

        $this->render('barang/adjust', [
            'pageTitle' => 'Penyesuaian Stok',
            'barang' => $barang,
        ]);
    }
}
