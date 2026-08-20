-- ============================================================
-- Koperasi Sekolah - Skema Database
-- MySQL/MariaDB, InnoDB, utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS koperasi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE koperasi;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS capital_transactions;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS payable_payments;
DROP TABLE IF EXISTS payables;
DROP TABLE IF EXISTS receivable_payments;
DROP TABLE IF EXISTS receivables;
DROP TABLE IF EXISTS cash_transactions;
DROP TABLE IF EXISTS transaction_details;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS koperasi_profile;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS number_counters;
SET FOREIGN_KEY_CHECKS = 1;

-- =========================== ROLE ===========================
CREATE TABLE roles (
  id            TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(50)      NOT NULL UNIQUE,
  description   VARCHAR(255)     NULL,
  created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ========================== USERS ===========================
CREATE TABLE users (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id              TINYINT UNSIGNED NOT NULL,
  username             VARCHAR(50)      NOT NULL UNIQUE,
  name                 VARCHAR(100)     NOT NULL,
  password_hash        VARCHAR(255)     NOT NULL,
  must_change_password TINYINT(1)       NOT NULL DEFAULT 0,
  is_active            TINYINT(1)       NOT NULL DEFAULT 1,
  created_at           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ====================== KOPERASI PROFILE ====================
CREATE TABLE koperasi_profile (
  id             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  nama_koperasi  VARCHAR(100)     NOT NULL DEFAULT '',
  nama_sekolah   VARCHAR(150)     NOT NULL DEFAULT '',
  alamat         TEXT             NULL,
  telepon        VARCHAR(30)      NULL,
  email          VARCHAR(100)     NULL,
  logo           VARCHAR(255)     NULL,
  nama_ketua     VARCHAR(100)     NULL,
  nama_bendahara VARCHAR(100)     NULL,
  created_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ========================= CATEGORIES =======================
-- type: barang | pemasukan | pengeluaran
CREATE TABLE categories (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100)    NOT NULL,
  type       ENUM('barang','pemasukan','pengeluaran') NOT NULL,
  created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_category (type, name),
  KEY idx_categories_type (type)
) ENGINE=InnoDB;

-- ========================= SUPPLIERS ========================
CREATE TABLE suppliers (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(150)    NOT NULL,
  phone      VARCHAR(30)     NULL,
  address    TEXT            NULL,
  is_active  TINYINT(1)      NOT NULL DEFAULT 1,
  created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_suppliers_name (name)
) ENGINE=InnoDB;

-- ========================= CUSTOMERS ========================
CREATE TABLE customers (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(150)    NOT NULL,
  phone      VARCHAR(30)     NULL,
  address    TEXT            NULL,
  is_active  TINYINT(1)      NOT NULL DEFAULT 1,
  created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_customers_name (name)
) ENGINE=InnoDB;

-- ========================= PRODUCTS =========================
CREATE TABLE products (
  id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  kode          VARCHAR(30)      NOT NULL UNIQUE,
  barcode       VARCHAR(50)      NULL,
  name          VARCHAR(150)     NOT NULL,
  category_id   BIGINT UNSIGNED  NULL,
  satuan        VARCHAR(20)      NOT NULL DEFAULT 'pcs',
  harga_beli    DECIMAL(15,2)    NOT NULL DEFAULT 0,
  harga_jual    DECIMAL(15,2)    NOT NULL DEFAULT 0,
  stock         DECIMAL(12,2)    NOT NULL DEFAULT 0,
  stock_minimum DECIMAL(12,2)    NOT NULL DEFAULT 0,
  supplier_id   BIGINT UNSIGNED  NULL,
  is_active     TINYINT(1)       NOT NULL DEFAULT 1,
  created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_products_name (name),
  KEY idx_products_category (category_id),
  KEY idx_products_supplier (supplier_id),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ======================== TRANSACTIONS ======================
-- type: penjualan | pembelian | pemasukan | pengeluaran
-- pembayaran piutang/hutang disimpan di tabel pembayaran masing-masing
CREATE TABLE transactions (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_transaksi   VARCHAR(30)     NOT NULL UNIQUE,
  type           ENUM('penjualan','pembelian','pemasukan','pengeluaran') NOT NULL,
  tahun_ajaran   VARCHAR(9)      NOT NULL,
  tanggal        DATE            NOT NULL,
  customer_id    BIGINT UNSIGNED NULL,
  supplier_id    BIGINT UNSIGNED NULL,
  category_id    BIGINT UNSIGNED NULL,
  total          DECIMAL(15,2)   NOT NULL DEFAULT 0,
  payment_method ENUM('tunai','kredit') NULL,
  status         ENUM('AKTIF','DIBATALKAN') NOT NULL DEFAULT 'AKTIF',
  alasan_batal   VARCHAR(255)    NULL,
  cancelled_by   BIGINT UNSIGNED NULL,
  cancelled_at   DATETIME        NULL,
  keterangan     TEXT            NULL,
  user_id        BIGINT UNSIGNED NOT NULL,
  created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_transactions_type (type),
  KEY idx_transactions_tahun_ajaran (tahun_ajaran),
  KEY idx_transactions_tanggal (tanggal),
  KEY idx_transactions_status (status),
  KEY idx_transactions_customer (customer_id),
  KEY idx_transactions_supplier (supplier_id),
  KEY idx_transactions_user (user_id),
  CONSTRAINT fk_transactions_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_transactions_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_transactions_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===================== TRANSACTION DETAILS ==================
CREATE TABLE transaction_details (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  transaction_id BIGINT UNSIGNED NOT NULL,
  product_id     BIGINT UNSIGNED NOT NULL,
  qty            DECIMAL(12,2)   NOT NULL DEFAULT 0,
  harga          DECIMAL(15,2)   NOT NULL DEFAULT 0,
  diskon         DECIMAL(15,2)   NOT NULL DEFAULT 0,
  subtotal       DECIMAL(15,2)   NOT NULL DEFAULT 0,
  created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_details_transaction (transaction_id),
  KEY idx_details_product (product_id),
  CONSTRAINT fk_details_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_details_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ====================== CASH TRANSACTIONS ===================
-- Sumber kebenaran tunggal untuk kas.
-- jenis: saldo_awal | masuk | keluar
CREATE TABLE cash_transactions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tahun_ajaran  VARCHAR(9)      NOT NULL,
  tanggal       DATE            NOT NULL,
  no_transaksi  VARCHAR(30)     NOT NULL UNIQUE,
  jenis         ENUM('saldo_awal','masuk','keluar') NOT NULL,
  kategori      VARCHAR(100)    NULL,
  nominal       DECIMAL(15,2)   NOT NULL DEFAULT 0,
  keterangan    VARCHAR(255)    NULL,
  status        ENUM('AKTIF','DIBATALKAN') NOT NULL DEFAULT 'AKTIF',
  related_type  VARCHAR(20)     NULL,
  related_id    BIGINT UNSIGNED NULL,
  user_id       BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cash_tahun_ajaran (tahun_ajaran),
  KEY idx_cash_tanggal (tanggal),
  KEY idx_cash_status (status),
  KEY idx_cash_jenis (jenis),
  KEY idx_cash_related (related_type, related_id),
  KEY idx_cash_user (user_id)
) ENGINE=InnoDB;

-- ========================= RECEIVABLES ======================
-- Piutang dari penjualan kredit
CREATE TABLE receivables (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id    BIGINT UNSIGNED NOT NULL,
  transaction_id BIGINT UNSIGNED NULL,
  tahun_ajaran   VARCHAR(9)      NOT NULL,
  tanggal        DATE            NOT NULL,
  no_transaksi   VARCHAR(30)     NOT NULL UNIQUE,
  total          DECIMAL(15,2)   NOT NULL DEFAULT 0,
  jatuh_tempo    DATE            NULL,
  status         ENUM('AKTIF','DIBATALKAN') NOT NULL DEFAULT 'AKTIF',
  created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_receivables_customer (customer_id),
  KEY idx_receivables_tahun_ajaran (tahun_ajaran),
  KEY idx_receivables_status (status),
  CONSTRAINT fk_receivables_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_receivables_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE receivable_payments (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  receivable_id BIGINT UNSIGNED NOT NULL,
  tanggal      DATE            NOT NULL,
  no_bukti     VARCHAR(30)     NOT NULL UNIQUE,
  nominal      DECIMAL(15,2)   NOT NULL DEFAULT 0,
  keterangan   VARCHAR(255)    NULL,
  status       ENUM('AKTIF','DIBATALKAN') NOT NULL DEFAULT 'AKTIF',
  user_id      BIGINT UNSIGNED NULL,
  created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rpayments_receivable (receivable_id),
  KEY idx_rpayments_status (status),
  CONSTRAINT fk_rpayments_receivable FOREIGN KEY (receivable_id) REFERENCES receivables(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ========================== PAYABLES ========================
-- Hutang dari pembelian kredit
CREATE TABLE payables (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  supplier_id    BIGINT UNSIGNED NOT NULL,
  transaction_id BIGINT UNSIGNED NULL,
  tahun_ajaran   VARCHAR(9)      NOT NULL,
  tanggal        DATE            NOT NULL,
  no_transaksi   VARCHAR(30)     NOT NULL UNIQUE,
  total          DECIMAL(15,2)   NOT NULL DEFAULT 0,
  jatuh_tempo    DATE            NULL,
  status         ENUM('AKTIF','DIBATALKAN') NOT NULL DEFAULT 'AKTIF',
  created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payables_supplier (supplier_id),
  KEY idx_payables_tahun_ajaran (tahun_ajaran),
  KEY idx_payables_status (status),
  CONSTRAINT fk_payables_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_payables_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payable_payments (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payable_id BIGINT UNSIGNED NOT NULL,
  tanggal    DATE            NOT NULL,
  no_bukti   VARCHAR(30)     NOT NULL UNIQUE,
  nominal    DECIMAL(15,2)   NOT NULL DEFAULT 0,
  keterangan VARCHAR(255)    NULL,
  status     ENUM('AKTIF','DIBATALKAN') NOT NULL DEFAULT 'AKTIF',
  user_id    BIGINT UNSIGNED NULL,
  created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_hpayments_payable (payable_id),
  KEY idx_hpayments_status (status),
  CONSTRAINT fk_hpayments_payable FOREIGN KEY (payable_id) REFERENCES payables(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ======================= STOCK MOVEMENTS ====================
-- Masuk (pembelian/penyesuaian +), Keluar (penjualan/penyesuaian -)
CREATE TABLE stock_movements (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id   BIGINT UNSIGNED NOT NULL,
  tahun_ajaran VARCHAR(9)      NOT NULL,
  tanggal      DATE            NOT NULL,
  no_referensi VARCHAR(30)     NULL,
  type         ENUM('masuk','keluar','penyesuaian') NOT NULL,
  qty          DECIMAL(12,2)   NOT NULL DEFAULT 0,
  keterangan   VARCHAR(255)    NULL,
  status       ENUM('AKTIF','DIBATALKAN') NOT NULL DEFAULT 'AKTIF',
  user_id      BIGINT UNSIGNED NULL,
  created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_stock_product (product_id),
  KEY idx_stock_tahun_ajaran (tahun_ajaran),
  KEY idx_stock_tanggal (tanggal),
  KEY idx_stock_status (status),
  CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===================== CAPITAL TRANSACTIONS =================
-- Modal koperasi (terpisah dari operasional)
CREATE TABLE capital_transactions (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tahun_ajaran VARCHAR(9)      NOT NULL,
  tanggal      DATE            NOT NULL,
  no_transaksi VARCHAR(30)     NOT NULL UNIQUE,
  type         ENUM('modal_awal','tambahan','pengurangan') NOT NULL,
  nominal      DECIMAL(15,2)   NOT NULL DEFAULT 0,
  keterangan   VARCHAR(255)    NULL,
  status       ENUM('AKTIF','DIBATALKAN') NOT NULL DEFAULT 'AKTIF',
  user_id      BIGINT UNSIGNED NULL,
  created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_capital_tahun_ajaran (tahun_ajaran),
  KEY idx_capital_status (status)
) ENGINE=InnoDB;

-- ======================== ATTACHMENTS =======================
CREATE TABLE attachments (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  related_type  VARCHAR(30)     NOT NULL,
  related_id    BIGINT UNSIGNED NOT NULL,
  stored_name   VARCHAR(255)    NOT NULL,
  original_name VARCHAR(255)    NOT NULL,
  mime          VARCHAR(100)    NULL,
  size          INT UNSIGNED    NULL,
  uploaded_by   BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attachments_related (related_type, related_id)
) ENGINE=InnoDB;

-- ========================= AUDIT LOGS =======================
CREATE TABLE audit_logs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NULL,
  username   VARCHAR(50)     NOT NULL DEFAULT 'system',
  aktivitas  VARCHAR(150)    NOT NULL,
  detail     TEXT            NULL,
  ip_address VARCHAR(45)     NULL,
  user_agent VARCHAR(255)    NULL,
  created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_created (created_at),
  KEY idx_audit_aktivitas (aktivitas)
) ENGINE=InnoDB;

-- ========================== SETTINGS ========================
CREATE TABLE settings (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`      VARCHAR(50)     NOT NULL UNIQUE,
  `value`    TEXT            NULL,
  updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ===================== NUMBER COUNTERS ======================
-- Pencacah nomor transaksi per prefix per hari (anti bentrok)
CREATE TABLE number_counters (
  prefix       VARCHAR(10) NOT NULL,
  tanggal      DATE        NOT NULL,
  last_number  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (prefix, tanggal)
) ENGINE=InnoDB;

-- ==================== LOGIN ATTEMPTS ========================
-- Pencatatan percobaan login (anti brute force)
CREATE TABLE login_attempts (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username    VARCHAR(50)     NOT NULL,
  ip          VARCHAR(45)     NOT NULL,
  attempt_at  DATETIME        NOT NULL,
  success     TINYINT(1)      NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_la_username (username),
  KEY idx_la_attempt_at (attempt_at)
) ENGINE=InnoDB;
