-- ============================================================
-- Koperasi Sekolah - Seed Data (master + pengguna default)
-- Password default semua akun: koperasi123
-- Password WAJIB diganti setelah login pertama.
-- ============================================================

USE koperasi;

-- ------------------------- ROLES ----------------------------
INSERT INTO roles (id, name, description) VALUES
  (1, 'Administrator', 'Akses penuh seluruh sistem'),
  (2, 'Bendahara',     'Mencatat transaksi, melihat laporan, mengelola kas'),
  (3, 'Petugas',       'Transaksi penjualan dan melihat barang');

-- ------------------------- USERS ----------------------------
-- hash password untuk "koperasi123"
INSERT INTO users (role_id, username, name, password_hash, must_change_password, is_active) VALUES
  (1, 'admin',     'Administrator Koperasi', '$2y$12$zyxvnpQ0BxkTcGzbLEIr3ushL7sbgbGMS0fvEzwg9mfaVG1bg2Rye', 1, 1),
  (2, 'bendahara', 'Bendahara Koperasi',     '$2y$12$zyxvnpQ0BxkTcGzbLEIr3ushL7sbgbGMS0fvEzwg9mfaVG1bg2Rye', 1, 1),
  (3, 'petugas',   'Petugas Toko',           '$2y$12$zyxvnpQ0BxkTcGzbLEIr3ushL7sbgbGMS0fvEzwg9mfaVG1bg2Rye', 1, 1);

-- -------------------- PROFIL KOPERASI -----------------------
INSERT INTO koperasi_profile (id, nama_koperasi, nama_sekolah, alamat, telepon, email, logo, nama_ketua, nama_bendahara) VALUES
  (1, 'Koperasi Sekolah', 'SMP Negeri 1 Harapan', 'Jl. Pendidikan No. 1', '0812-3456-7890', 'koperasi@sekolah.sch.id', NULL, 'Drs. Ahmad Suryadi', 'Sri Wahyuni, S.Pd');

-- ----------------------- CATEGORIES -------------------------
INSERT INTO categories (name, type) VALUES
  ('Alat Tulis',   'barang'),
  ('Buku',         'barang'),
  ('Seragam',      'barang'),
  ('Atribut',      'barang'),
  ('Makanan',      'barang'),
  ('Minuman',      'barang'),
  ('Jasa',             'pemasukan'),
  ('Kontribusi Anggota','pemasukan'),
  ('Pendapatan Administrasi','pemasukan'),
  ('Bunga',            'pemasukan'),
  ('Pendapatan Lainnya','pemasukan'),
  ('Listrik',          'pengeluaran'),
  ('Air',              'pengeluaran'),
  ('ATK',              'pengeluaran'),
  ('Transportasi',     'pengeluaran'),
  ('Perawatan',        'pengeluaran'),
  ('Gaji / Honor',     'pengeluaran'),
  ('Perlengkapan',     'pengeluaran'),
  ('Biaya Administrasi','pengeluaran'),
  ('Biaya Lainnya',    'pengeluaran');

-- ----------------------- SUPPLIERS --------------------------
INSERT INTO suppliers (name, phone, address) VALUES
  ('Toko Buku Sejahtera', '021-555-0101', 'Pasar Sentral Blok A'),
  ('CV Alat Tulis Nusantara', '021-555-0102', 'Jl. Niaga No. 12'),
  ('UD Seragam Mandiri', '021-555-0103', 'Jl. Industri No. 3'),
  ('Distributor Snack Berkah', '021-555-0104', 'Gudang Pangan, Blok C');

-- ----------------------- CUSTOMERS --------------------------
INSERT INTO customers (name, phone, address) VALUES
  ('Siswa Kelas 7A', NULL, 'Sekolah'),
  ('Siswa Kelas 7B', NULL, 'Sekolah'),
  ('Siswa Kelas 8A', NULL, 'Sekolah'),
  ('Siswa Kelas 9A', NULL, 'Sekolah'),
  ('Guru & Karyawan', NULL, 'Sekolah'),
  ('Umum', NULL, NULL);

-- ------------------------ PRODUCTS --------------------------
INSERT INTO products (kode, barcode, name, category_id, satuan, harga_beli, harga_jual, stock, stock_minimum, supplier_id, is_active) VALUES
  ('BRG-001', NULL, 'Buku Tulis 38 Lembar',   2, 'pcs', 2500, 3500, 100, 10, 1, 1),
  ('BRG-002', NULL, 'Pensil 2B',              1, 'pcs', 1500, 2500, 120, 20, 2, 1),
  ('BRG-003', NULL, 'Pulpen Standard',        1, 'pcs', 1800, 3000, 150, 20, 2, 1),
  ('BRG-004', NULL, 'Penghapus',              1, 'pcs', 1000, 2000, 80, 10, 2, 1),
  ('BRG-005', NULL, 'Penggaris 30 cm',        1, 'pcs', 2000, 3500, 60, 10, 2, 1),
  ('BRG-006', NULL, 'Buku Gambar A4',         2, 'pcs', 4000, 6000, 50, 10, 1, 1),
  ('BRG-007', NULL, 'Seragam Batik Sekolah',  3, 'pcs', 95000, 110000, 20, 5, 3, 1),
  ('BRG-008', NULL, 'Topi Sekolah',           4, 'pcs', 20000, 28000, 15, 5, 3, 1),
  ('BRG-009', NULL, 'Snack Coklat',           5, 'pcs', 2000, 3000, 200, 30, 4, 1),
  ('BRG-010', NULL, 'Air Mineral 600ml',      6, 'botol', 2500, 4000, 150, 30, 4, 1);

-- ----------------------- SETTINGS ---------------------------
INSERT INTO settings (`key`, `value`) VALUES
  ('allow_negative_cash', '0'),
  ('allow_negative_stock', '0'),
  ('laporan_singkat', 'Laba/Rugi Sederhana (non-akuntansi)');
