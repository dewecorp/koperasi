# Aplikasi Pencatatan Keuangan Koperasi Sekolah

Aplikasi web ringan untuk pengurus koperasi sekolah: mencatat pemasukan, pengeluaran,
penjualan, pembelian, kas, piutang, hutang, stok, modal, dan membuat laporan keuangan
secara otomatis. Sederhana, mudah digunakan, dan dapat dipertanggungjawabkan.

**Prioritas desain:** AKURAT → MUDAH → RINGAN → AMAN → RAPI

---

## 1. Kebutuhan Server

- PHP **7.4+** (disarankan 8.x)
- MySQL atau MariaDB
- Web server Apache (XAMPP / Laragon) atau PHP built-in server
- Ekstensi PHP yang dibutuhkan: `pdo_mysql`, `fileinfo`, `session`

Tidak memerlukan layanan cloud / API eksternal. Tailwind CSS dan Chart.js
diunduh dan disimpan lokal di `public/assets/`.

## 2. Versi PHP

- Minimum: PHP 7.4
- Direkomendasikan: PHP 8.0 – 8.4
- Dikembangkan & diuji pada PHP 8.4 + MySQL 8.4

## 3. Cara Membuat Database

Lewat phpMyAdmin atau CLI MySQL:

```sql
CREATE DATABASE koperasi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

## 4. Cara Import Database

Buka terminal pada folder project, lalu:

```bash
# Windows (aplikasi CLI mysql bawaan Laragon boleh dipakai)
mysql -u root -p koperasi < database/schema.sql
mysql -u root -p koperasi < database/seed.sql
```

Atau lewat **phpMyAdmin**: buat database `koperasi` → tab *Import* → pilih
`database/schema.sql`, jalankan. Ulangi untuk `database/seed.sql`.

> `schema.sql` berisi `DROP TABLE IF EXISTS` dan `CREATE DATABASE`, aman untuk impor ulang.

## 5. Konfigurasi Database

Semua konfigurasi database ada di **satu file**: `config/database.php`.

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'koperasi');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Default cocok untuk XAMPP/Laragon (root tanpa password). Sesuaikan bila perlu.
Seluruh query memakai **PDO prepared statement**.

## 6. Cara Menjalankan Aplikasi

### Opsi A — Laragon / XAMPP (disarankan)

1. Letakkan folder project di `laragon/www/koperasi` (Laragon) atau `htdocs/koperasi` (XAMPP).
2. Nyalakan Apache + MySQL.
3. Buat & import database (lihat nomor 3–4).
4. Buka di browser:
   - Laragon: `http://koperasi.test`
   - XAMPP:    `http://localhost/koperasi`

Aplikasi bekerja langsung dengan docroot di folder *project* maupun di folder
`public/`. Jika vhost mengarah ke root project, `.htaccess` atau `index.php`
akan menyambungkannya ke `public/`.

### URL Bersih (Clean URL)

Aplikasi memakai URL bersih tanpa `index.php`/`.php`:

```
/koperasi/
/koperasi/login
/koperasi/dashboard
/koperasi/barang
/koperasi/transaksi/penjualan
/koperasi/transaksi/pembelian
/koperasi/keuangan/kas
/koperasi/keuangan/piutang
/koperasi/laporan/kas
/koperasi/laporan/penjualan
/koperasi/pengaturan
```

- **Apache**: mod_rewrite + `.htaccess` (di root project dan di `public/`).
- Semua link internal dibangkitkan lewat helper `url()` — tidak ada URL
  hard-code dengan `.php`.
- `index.php` dan `*.php` di URL otomatis di-*redirect* 301 ke versi bersih.
- Seluruh notifikasi/konfirmasi memakai **SweetAlert2** lokal
  (`public/assets/vendor/sweetalert2/`), bukan `alert()/confirm()` native.

### Opsi B — PHP built-in server

```bash
cd koperasi
php -S localhost:8000 -t public public/router.php
```

Buka `http://localhost:8000`.

> `router.php` hanya untuk mode development (server built-in tidak membaca
> `.htaccess`). Pada Apache/XAMPP/Laragon, clean URL ditangani `.htaccess`.

## 7. Akun Administrator Default

| Username | Password | Peran |
|---|---|---|
| `admin` | `koperasi123` | Administrator |

SETIAP akun default **wajib mengganti password saat login pertama**.

## 8. Akun Bendahara Default

| Username | Password | Peran |
|---|---|---|
| `bendahara` | `koperasi123` | Bendahara |

## 9. Akun Petugas Default

| Username | Password | Peran |
|---|---|---|
| `petugas` | `koperasi123` | Petugas |

## 10. Cara Backup Database

1. Login sebagai **Administrator**.
2. Menu **Pengaturan → Backup / Restore**.
3. Klik **Backup Database Sekarang**.
4. File `.sql` tersimpan di `storage/backups/` (di luar `public/`, tidak bisa
   diakses langsung lewat URL).
5. Unduh file dengan tombol download.

## 11. Cara Restore Database

> PERINGATAN: restore akan MENGGANTI seluruh data saat ini dan tidak dapat dibatalkan.

1. Login sebagai **Administrator**.
2. Menu **Pengaturan → Backup / Restore**.
3. Pilih file dari daftar backup lalu klik tombol restore, **atau** unggah file
   `.sql` pada bagian *Restore dari File*.
4. Setujui dialog konfirmasi.

## 12. Struktur Folder

```
koperasi/
│
├── config/
│   ├── app.php              # konstanta aplikasi (nama, batas upload, folder)
│   └── database.php         # koneksi PDO (config tunggal)
│
├── public/                  # docroot (entry utama)
│   ├── index.php            # front controller
│   ├── assets/              # css, js, tailwind, chart.js (lokal)
│   ├── uploads/             # bukti transaksi & logo (PHP dilarang dieksekusi)
│   └── .htaccess
│
├── app/
│   ├── controllers/         # AuthController, PenjualanController, dst.
│   ├── models/              # Model dasar
│   ├── views/               # layout + tampilan per halaman
│   ├── helpers/             # functions.php, auth.php (url, csrf, format)
│   ├── middleware/          # (auth dipakai lewat app/helpers/auth.php)
│   ├── core/                # App (router), Controller, Model
│   └── services/
│       ├── FinanceService.php  # jantung logika kas/stok/piutang/hutang
│       └── BackupService.php   # backup/restore tanpa library eksternal
│
├── database/
│   ├── schema.sql           # struktur lengkap
│   └── seed.sql             # master data + 3 akun default
│
├── scripts/
│   ├── seed_demo.php        # data transaksi contoh
│   └── test_keuangan.php    # test skenario keuangan (7 tes + validasi)
│
├── storage/backups/         # file backup SQL
├── index.php                # entry alternatif bila docroot = root project
├── .htaccess                # arahkan request ke public/
└── README.md
```

## 13. Cara Mengembangkan Aplikasi

### Pola alur request

1. Semua request masuk ke `public/index.php`.
2. `app/core/App.php` (router) memetakan `?page=...&action=...&id=...` ke controller.
3. Controller memanggil service (logika keuangan) lalu merender view.
4. View memakai helper `url()`, `asset()`, `csrf_field()`, `rupiah()`, `icon()`, dll.

### Menambah halaman baru (contoh: halaman "X")

1. Buat `app/controllers/XController.php` dengan aksi `index()`.
2. Daftarkan di `$map` pada `app/core/App.php`.
3. Tambahkan menu di `app/views/layouts/app.php`.
4. Buat view di `app/views/x/index.php`.

### Aturan penting

- **Jangan hard-delete transaksi keuangan.** Gunakan pembatalan (status
  `DIBATALKAN` + alasan) yang tersedia di tiap modul. Efek kas/stok/piutang/hutang
  dibalik otomatis di `FinanceService`.
- **Jangan simpan saldo sebagai nilai.** Saldo kas selalu dihitung dari
  `Saldo Awal + Kas Masuk - Kas Keluar` berdasarkan `cash_transactions`.
  Sisa piutang/hutang dan stok juga dihitung ulang dari transaksi aktif.
- **Transaksi antar-tabel** selalu dibungkus `BEGIN ... COMMIT / ROLLBACK`.
- Semua query memakai **prepared statement**, semua output lewat escaping `e()`.
- Setiap form penting memakai **CSRF token** (`csrf_field()`).
- Hak akses diperiksa di **server** melalui `require_role()`/`guard()`,
  bukan sekadar menyembunyikan menu.

### Menjalankan test keuangan

```bash
php scripts/test_keuangan.php
```

Menjalankan skenario: saldo awal → penjualan tunai → pembelian tunai →
penjualan kredit → bayar piutang → pembelian kredit → bayar hutang →
pembatalan penjualan, serta validasi stok & sisa piutang. Semua harus `PASS`.

### Data demo

```bash
php scripts/seed_demo.php
```

Menghapus seluruh transaksi yang ada lalu mengisi data contoh (pembelian,
penjualan, pemasukan, pengeluaran, pinjaman, modal, bayar piutang/hutang)
agar dashboard dan laporan langsung terisi. Master data tidak dihapus.

## 14. Catatan Laporan Laba/Rugi

Laporan **Laba/Rugi Sederhana** menggunakan:

```
Pendapatan Penjualan
- Harga Pokok Penjualan (harga beli × qty terjual)
- Biaya Operasional (pengeluaran)
+ Pemasukan Lain (non-penjualan)
= Laba/Rugi Bersih
```

Ini **bukan** laporan laba-rugi akuntansi double-entry penuh, sehingga diberi
label resmi "Laporan Laba/Rugi Sederhana (non-akuntansi)" pada halaman laporan.

---

© Koperasi Sekolah — aplikasi administrasi koperasi sederhana.