-- Koperasi Sekolah - Backup Database
-- Tanggal: 2026-08-19 11:31:05
-- DB: koperasi

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `related_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_id` bigint unsigned NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` int unsigned DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attachments_related` (`related_type`,`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `aktivitas` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_aktivitas` (`aktivitas`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`id`,`user_id`,`username`,`aktivitas`,`detail`,`ip_address`,`user_agent`,`created_at`) VALUES ('1','1','admin','TAMBAH PENJUALAN','total: Rp 17.000','127.0.0.1','curl/8.18.0','2026-08-19 11:19:38');
INSERT INTO `audit_logs` (`id`,`user_id`,`username`,`aktivitas`,`detail`,`ip_address`,`user_agent`,`created_at`) VALUES ('2',NULL,'system','LOGIN GAGAL','Username: admin','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0','2026-08-19 11:29:26');

DROP TABLE IF EXISTS `capital_transactions`;
CREATE TABLE `capital_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `no_transaksi` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('modal_awal','tambahan','pengurangan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('AKTIF','DIBATALKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  KEY `idx_capital_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `cash_transactions`;
CREATE TABLE `cash_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `no_transaksi` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('saldo_awal','masuk','keluar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('AKTIF','DIBATALKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `related_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  KEY `idx_cash_tanggal` (`tanggal`),
  KEY `idx_cash_status` (`status`),
  KEY `idx_cash_jenis` (`jenis`),
  KEY `idx_cash_related` (`related_type`,`related_id`),
  KEY `idx_cash_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cash_transactions` (`id`,`tanggal`,`no_transaksi`,`jenis`,`kategori`,`nominal`,`keterangan`,`status`,`related_type`,`related_id`,`user_id`,`created_at`,`updated_at`) VALUES ('1','2026-01-05','SA-AWAL','saldo_awal','Saldo Awal','5000000.00','Saldo awal test 2','AKTIF',NULL,NULL,'1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `cash_transactions` (`id`,`tanggal`,`no_transaksi`,`jenis`,`kategori`,`nominal`,`keterangan`,`status`,`related_type`,`related_id`,`user_id`,`created_at`,`updated_at`) VALUES ('2','2026-01-06','PJ-20260819-0000','masuk','Penjualan','500000.00','Penjualan tunai PJ-20260819-0000','DIBATALKAN','transactions','1','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `cash_transactions` (`id`,`tanggal`,`no_transaksi`,`jenis`,`kategori`,`nominal`,`keterangan`,`status`,`related_type`,`related_id`,`user_id`,`created_at`,`updated_at`) VALUES ('3','2026-01-07','PB-20260819-0000','keluar','Pembelian','980000.00','Pembelian tunai PB-20260819-0000','AKTIF','transactions','2','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `cash_transactions` (`id`,`tanggal`,`no_transaksi`,`jenis`,`kategori`,`nominal`,`keterangan`,`status`,`related_type`,`related_id`,`user_id`,`created_at`,`updated_at`) VALUES ('4','2026-01-09','BYR-PIU-20260819-0000','masuk','Pembayaran Piutang','100000.00','Bayar sebagian','AKTIF','receivable_payments','1','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `cash_transactions` (`id`,`tanggal`,`no_transaksi`,`jenis`,`kategori`,`nominal`,`keterangan`,`status`,`related_type`,`related_id`,`user_id`,`created_at`,`updated_at`) VALUES ('5','2026-01-11','BYR-HUT-20260819-0000','keluar','Pembayaran Hutang','200000.00','Angsuran','AKTIF','payable_payments','1','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `cash_transactions` (`id`,`tanggal`,`no_transaksi`,`jenis`,`kategori`,`nominal`,`keterangan`,`status`,`related_type`,`related_id`,`user_id`,`created_at`,`updated_at`) VALUES ('6','2026-08-19','PJ-20260819-0002','masuk','Penjualan','17000.00','Penjualan tunai PJ-20260819-0002','AKTIF','transactions','6','1','2026-08-19 11:19:38','2026-08-19 11:19:38');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('barang','pemasukan','pengeluaran') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category` (`type`,`name`),
  KEY `idx_categories_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('1','Alat Tulis','barang','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('2','Buku','barang','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('3','Seragam','barang','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('4','Atribut','barang','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('5','Makanan','barang','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('6','Minuman','barang','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('7','Jasa','pemasukan','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('8','Kontribusi Anggota','pemasukan','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('9','Pendapatan Administrasi','pemasukan','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('10','Bunga','pemasukan','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('11','Pendapatan Lainnya','pemasukan','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('12','Listrik','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('13','Air','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('14','ATK','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('15','Transportasi','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('16','Perawatan','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('17','Gaji / Honor','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('18','Perlengkapan','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('19','Biaya Administrasi','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `categories` (`id`,`name`,`type`,`created_at`,`updated_at`) VALUES ('20','Biaya Lainnya','pengeluaran','2026-08-19 10:50:25','2026-08-19 10:50:25');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customers_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('1','Siswa Kelas 7A',NULL,'Sekolah','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `customers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('2','Siswa Kelas 7B',NULL,'Sekolah','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `customers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('3','Siswa Kelas 8A',NULL,'Sekolah','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `customers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('4','Siswa Kelas 9A',NULL,'Sekolah','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `customers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('5','Guru & Karyawan',NULL,'Sekolah','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `customers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('6','Umum',NULL,NULL,'1','2026-08-19 10:50:25','2026-08-19 10:50:25');

DROP TABLE IF EXISTS `koperasi_profile`;
CREATE TABLE `koperasi_profile` (
  `id` tinyint unsigned NOT NULL DEFAULT '1',
  `nama_koperasi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nama_sekolah` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `telepon` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_ketua` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_bendahara` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `koperasi_profile` (`id`,`nama_koperasi`,`nama_sekolah`,`alamat`,`telepon`,`email`,`logo`,`nama_ketua`,`nama_bendahara`,`created_at`,`updated_at`) VALUES ('1','Koperasi Sekolah','SMP Negeri 1 Harapan','Jl. Pendidikan No. 1','0812-3456-7890','koperasi@sekolah.sch.id',NULL,'Drs. Ahmad Suryadi','Sri Wahyuni, S.Pd','2026-08-19 10:50:25','2026-08-19 10:50:25');

DROP TABLE IF EXISTS `number_counters`;
CREATE TABLE `number_counters` (
  `prefix` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `last_number` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`prefix`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `number_counters` (`prefix`,`tanggal`,`last_number`) VALUES ('BYR-HUT','2026-08-19','0');
INSERT INTO `number_counters` (`prefix`,`tanggal`,`last_number`) VALUES ('BYR-PIU','2026-08-19','0');
INSERT INTO `number_counters` (`prefix`,`tanggal`,`last_number`) VALUES ('PB','2026-08-19','1');
INSERT INTO `number_counters` (`prefix`,`tanggal`,`last_number`) VALUES ('PJ','2026-08-19','2');

DROP TABLE IF EXISTS `payable_payments`;
CREATE TABLE `payable_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payable_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `no_bukti` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('AKTIF','DIBATALKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_bukti` (`no_bukti`),
  KEY `idx_hpayments_payable` (`payable_id`),
  KEY `idx_hpayments_status` (`status`),
  CONSTRAINT `fk_hpayments_payable` FOREIGN KEY (`payable_id`) REFERENCES `payables` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payable_payments` (`id`,`payable_id`,`tanggal`,`no_bukti`,`nominal`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('1','1','2026-01-11','BYR-HUT-20260819-0000','200000.00','Angsuran','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');

DROP TABLE IF EXISTS `payables`;
CREATE TABLE `payables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint unsigned NOT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `tanggal` date NOT NULL,
  `no_transaksi` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `jatuh_tempo` date DEFAULT NULL,
  `status` enum('AKTIF','DIBATALKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  KEY `idx_payables_supplier` (`supplier_id`),
  KEY `idx_payables_status` (`status`),
  KEY `fk_payables_transaction` (`transaction_id`),
  CONSTRAINT `fk_payables_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_payables_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payables` (`id`,`supplier_id`,`transaction_id`,`tanggal`,`no_transaksi`,`total`,`jatuh_tempo`,`status`,`created_at`,`updated_at`) VALUES ('1','2','4','2026-01-10','PB-20260819-0001','486000.00',NULL,'AKTIF','2026-08-19 11:19:26','2026-08-19 11:19:26');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `satuan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `harga_beli` decimal(15,2) NOT NULL DEFAULT '0.00',
  `harga_jual` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stock` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock_minimum` decimal(12,2) NOT NULL DEFAULT '0.00',
  `supplier_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  KEY `idx_products_name` (`name`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_supplier` (`supplier_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('1','BRG-001',NULL,'Buku Tulis 38 Lembar','2','pcs','2500.00','3500.00','357.00','10.00','1','1','2026-08-19 10:50:25','2026-08-19 11:19:38');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('2','BRG-002',NULL,'Pensil 2B','1','pcs','1500.00','2500.00','20.00','20.00','2','1','2026-08-19 10:50:25','2026-08-19 11:19:26');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('3','BRG-003',NULL,'Pulpen Standard','1','pcs','1800.00','3000.00','150.00','20.00','2','1','2026-08-19 10:50:25','2026-08-19 11:19:26');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('4','BRG-004',NULL,'Penghapus','1','pcs','1000.00','2000.00','250.00','10.00','2','1','2026-08-19 10:50:25','2026-08-19 11:19:26');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('5','BRG-005',NULL,'Penggaris 30 cm','1','pcs','2000.00','3500.00','98.00','10.00','2','1','2026-08-19 10:50:25','2026-08-19 11:19:38');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('6','BRG-006',NULL,'Buku Gambar A4','2','pcs','4000.00','6000.00','0.00','10.00','1','1','2026-08-19 10:50:25','2026-08-19 11:17:41');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('7','BRG-007',NULL,'Seragam Batik Sekolah','3','pcs','95000.00','110000.00','0.00','5.00','3','1','2026-08-19 10:50:25','2026-08-19 11:17:41');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('8','BRG-008',NULL,'Topi Sekolah','4','pcs','20000.00','28000.00','0.00','5.00','3','1','2026-08-19 10:50:25','2026-08-19 11:17:41');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('9','BRG-009',NULL,'Snack Coklat','5','pcs','2000.00','3000.00','300.00','30.00','4','1','2026-08-19 10:50:25','2026-08-19 11:19:26');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('10','BRG-010',NULL,'Air Mineral 600ml','6','botol','2500.00','4000.00','0.00','30.00','4','1','2026-08-19 10:50:25','2026-08-19 11:17:41');
INSERT INTO `products` (`id`,`kode`,`barcode`,`name`,`category_id`,`satuan`,`harga_beli`,`harga_jual`,`stock`,`stock_minimum`,`supplier_id`,`is_active`,`created_at`,`updated_at`) VALUES ('11','BRG-011','','Kotak Pensil','1','pcs','5000.00','8000.00','0.00','5.00','1','1','2026-08-19 11:13:01','2026-08-19 11:17:41');

DROP TABLE IF EXISTS `receivable_payments`;
CREATE TABLE `receivable_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receivable_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `no_bukti` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('AKTIF','DIBATALKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_bukti` (`no_bukti`),
  KEY `idx_rpayments_receivable` (`receivable_id`),
  KEY `idx_rpayments_status` (`status`),
  CONSTRAINT `fk_rpayments_receivable` FOREIGN KEY (`receivable_id`) REFERENCES `receivables` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `receivable_payments` (`id`,`receivable_id`,`tanggal`,`no_bukti`,`nominal`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('1','1','2026-01-09','BYR-PIU-20260819-0000','100000.00','Bayar sebagian','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');

DROP TABLE IF EXISTS `receivables`;
CREATE TABLE `receivables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `tanggal` date NOT NULL,
  `no_transaksi` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `jatuh_tempo` date DEFAULT NULL,
  `status` enum('AKTIF','DIBATALKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  KEY `idx_receivables_customer` (`customer_id`),
  KEY `idx_receivables_status` (`status`),
  KEY `fk_receivables_transaction` (`transaction_id`),
  CONSTRAINT `fk_receivables_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_receivables_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `receivables` (`id`,`customer_id`,`transaction_id`,`tanggal`,`no_transaksi`,`total`,`jatuh_tempo`,`status`,`created_at`,`updated_at`) VALUES ('1','1','3','2026-01-08','PJ-20260819-0001','200000.00',NULL,'AKTIF','2026-08-19 11:19:26','2026-08-19 11:19:26');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('1','Administrator','Akses penuh seluruh sistem','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `roles` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('2','Bendahara','Mencatat transaksi, melihat laporan, mengelola kas','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `roles` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('3','Petugas','Transaksi penjualan dan melihat barang','2026-08-19 10:50:25','2026-08-19 10:50:25');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`,`key`,`value`,`updated_at`) VALUES ('1','allow_negative_cash','0','2026-08-19 10:50:25');
INSERT INTO `settings` (`id`,`key`,`value`,`updated_at`) VALUES ('2','allow_negative_stock','0','2026-08-19 10:50:25');
INSERT INTO `settings` (`id`,`key`,`value`,`updated_at`) VALUES ('3','laporan_singkat','Laba/Rugi Sederhana (non-akuntansi)','2026-08-19 10:50:25');

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `no_referensi` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('masuk','keluar','penyesuaian') COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('AKTIF','DIBATALKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_product` (`product_id`),
  KEY `idx_stock_tanggal` (`tanggal`),
  KEY `idx_stock_status` (`status`),
  CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('1','1','2026-08-19',NULL,'penyesuaian','100.00','Stok awal test','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('2','3','2026-08-19',NULL,'penyesuaian','150.00','Stok awal test','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('3','9','2026-08-19',NULL,'penyesuaian','200.00','Stok awal test','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('4','1','2026-01-06','PJ-20260819-0000','keluar','100.00','Penjualan PJ-20260819-0000','DIBATALKAN','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('5','3','2026-01-06','PJ-20260819-0000','keluar','50.00','Penjualan PJ-20260819-0000','DIBATALKAN','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('6','1','2026-01-07','PB-20260819-0000','masuk','200.00','Pembelian PB-20260819-0000','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('7','4','2026-01-07','PB-20260819-0000','masuk','250.00','Pembelian PB-20260819-0000','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('8','5','2026-01-07','PB-20260819-0000','masuk','100.00','Pembelian PB-20260819-0000','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('9','2','2026-01-07','PB-20260819-0000','masuk','20.00','Pembelian PB-20260819-0000','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('10','1','2026-01-08','PJ-20260819-0001','keluar','40.00','Penjualan PJ-20260819-0001','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('11','3','2026-01-08','PJ-20260819-0001','keluar','20.00','Penjualan PJ-20260819-0001','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('12','1','2026-01-10','PB-20260819-0001','masuk','100.00','Pembelian PB-20260819-0001','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('13','9','2026-01-10','PB-20260819-0001','masuk','100.00','Pembelian PB-20260819-0001','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('14','3','2026-01-10','PB-20260819-0001','masuk','20.00','Pembelian PB-20260819-0001','AKTIF','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('15','1','2026-08-19','PJ-20260819-0002','keluar','3.00','Penjualan PJ-20260819-0002','AKTIF','1','2026-08-19 11:19:38','2026-08-19 11:19:38');
INSERT INTO `stock_movements` (`id`,`product_id`,`tanggal`,`no_referensi`,`type`,`qty`,`keterangan`,`status`,`user_id`,`created_at`,`updated_at`) VALUES ('16','5','2026-08-19','PJ-20260819-0002','keluar','2.00','Penjualan PJ-20260819-0002','AKTIF','1','2026-08-19 11:19:38','2026-08-19 11:19:38');

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('1','Toko Buku Sejahtera','021-555-0101','Pasar Sentral Blok A','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `suppliers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('2','CV Alat Tulis Nusantara','021-555-0102','Jl. Niaga No. 12','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `suppliers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('3','UD Seragam Mandiri','021-555-0103','Jl. Industri No. 3','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `suppliers` (`id`,`name`,`phone`,`address`,`is_active`,`created_at`,`updated_at`) VALUES ('4','Distributor Snack Berkah','021-555-0104','Gudang Pangan, Blok C','1','2026-08-19 10:50:25','2026-08-19 10:50:25');

DROP TABLE IF EXISTS `transaction_details`;
CREATE TABLE `transaction_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `qty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(15,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_details_transaction` (`transaction_id`),
  KEY `idx_details_product` (`product_id`),
  CONSTRAINT `fk_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_details_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('1','1','1','100.00','3500.00','0.00','350000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('2','1','3','50.00','3000.00','0.00','150000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('3','2','1','200.00','2500.00','0.00','500000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('4','2','4','250.00','1000.00','0.00','250000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('5','2','5','100.00','2000.00','0.00','200000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('6','2','2','20.00','1500.00','0.00','30000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('7','3','1','40.00','3500.00','0.00','140000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('8','3','3','20.00','3000.00','0.00','60000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('9','4','1','100.00','2500.00','0.00','250000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('10','4','9','100.00','2000.00','0.00','200000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('11','4','3','20.00','1800.00','0.00','36000.00','2026-08-19 11:19:26');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('13','6','1','3.00','3500.00','0.00','10500.00','2026-08-19 11:19:38');
INSERT INTO `transaction_details` (`id`,`transaction_id`,`product_id`,`qty`,`harga`,`diskon`,`subtotal`,`created_at`) VALUES ('14','6','5','2.00','3500.00','500.00','6500.00','2026-08-19 11:19:38');

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('penjualan','pembelian','pemasukan','pengeluaran') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('tunai','kredit') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('AKTIF','DIBATALKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `alasan_batal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  KEY `idx_transactions_type` (`type`),
  KEY `idx_transactions_tanggal` (`tanggal`),
  KEY `idx_transactions_status` (`status`),
  KEY `idx_transactions_customer` (`customer_id`),
  KEY `idx_transactions_supplier` (`supplier_id`),
  KEY `idx_transactions_user` (`user_id`),
  KEY `fk_transactions_category` (`category_id`),
  CONSTRAINT `fk_transactions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transactions` (`id`,`no_transaksi`,`type`,`tanggal`,`customer_id`,`supplier_id`,`category_id`,`total`,`payment_method`,`status`,`alasan_batal`,`cancelled_by`,`cancelled_at`,`keterangan`,`user_id`,`created_at`,`updated_at`) VALUES ('1','PJ-20260819-0000','penjualan','2026-01-06','1',NULL,NULL,'500000.00','tunai','DIBATALKAN','Test: batal transaksi','1','2026-08-19 11:19:26','','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `transactions` (`id`,`no_transaksi`,`type`,`tanggal`,`customer_id`,`supplier_id`,`category_id`,`total`,`payment_method`,`status`,`alasan_batal`,`cancelled_by`,`cancelled_at`,`keterangan`,`user_id`,`created_at`,`updated_at`) VALUES ('2','PB-20260819-0000','pembelian','2026-01-07',NULL,'1',NULL,'980000.00','tunai','AKTIF',NULL,NULL,NULL,'','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `transactions` (`id`,`no_transaksi`,`type`,`tanggal`,`customer_id`,`supplier_id`,`category_id`,`total`,`payment_method`,`status`,`alasan_batal`,`cancelled_by`,`cancelled_at`,`keterangan`,`user_id`,`created_at`,`updated_at`) VALUES ('3','PJ-20260819-0001','penjualan','2026-01-08','1',NULL,NULL,'200000.00','kredit','AKTIF',NULL,NULL,NULL,'','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `transactions` (`id`,`no_transaksi`,`type`,`tanggal`,`customer_id`,`supplier_id`,`category_id`,`total`,`payment_method`,`status`,`alasan_batal`,`cancelled_by`,`cancelled_at`,`keterangan`,`user_id`,`created_at`,`updated_at`) VALUES ('4','PB-20260819-0001','pembelian','2026-01-10',NULL,'2',NULL,'486000.00','kredit','AKTIF',NULL,NULL,NULL,'','1','2026-08-19 11:19:26','2026-08-19 11:19:26');
INSERT INTO `transactions` (`id`,`no_transaksi`,`type`,`tanggal`,`customer_id`,`supplier_id`,`category_id`,`total`,`payment_method`,`status`,`alasan_batal`,`cancelled_by`,`cancelled_at`,`keterangan`,`user_id`,`created_at`,`updated_at`) VALUES ('6','PJ-20260819-0002','penjualan','2026-08-19',NULL,NULL,NULL,'17000.00','tunai','AKTIF',NULL,NULL,NULL,'Test HTTP','1','2026-08-19 11:19:38','2026-08-19 11:19:38');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` tinyint unsigned NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`,`role_id`,`username`,`name`,`password_hash`,`must_change_password`,`is_active`,`created_at`,`updated_at`) VALUES ('1','1','admin','Administrator Koperasi','$2y$12$W/OhWIOza0Wz0xcP9rUyxOZHYE6MiWB2ZhNuY7ylUv5jjPC.t4Re.','0','1','2026-08-19 10:50:25','2026-08-19 11:08:22');
INSERT INTO `users` (`id`,`role_id`,`username`,`name`,`password_hash`,`must_change_password`,`is_active`,`created_at`,`updated_at`) VALUES ('2','2','bendahara','Bendahara Koperasi','$2y$12$zyxvnpQ0BxkTcGzbLEIr3ushL7sbgbGMS0fvEzwg9mfaVG1bg2Rye','1','1','2026-08-19 10:50:25','2026-08-19 10:50:25');
INSERT INTO `users` (`id`,`role_id`,`username`,`name`,`password_hash`,`must_change_password`,`is_active`,`created_at`,`updated_at`) VALUES ('3','3','petugas','Petugas Toko','$2y$12$zyxvnpQ0BxkTcGzbLEIr3ushL7sbgbGMS0fvEzwg9mfaVG1bg2Rye','1','1','2026-08-19 10:50:25','2026-08-19 10:50:25');

SET FOREIGN_KEY_CHECKS=1;
