<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * ============================================================
 * Helper dasar: URL, escaping, format, CSRF, flash, audit log
 * ============================================================
 */

function site_root(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if ($scriptDir === '/' || $scriptDir === '.') {
        $scriptDir = '';
    }
    // Hapus "/public" bila berada di dalamnya supaya URL konsisten
    $scriptDir = preg_replace('#/public$#', '', $scriptDir);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . $scriptDir;
}

/** URL untuk route aplikasi (clean URL, tanpa index.php/.php). */
function url(string $page, array $params = []): string
{
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    $path = route_path($page, $params);
    $qs = http_build_query($params);
    return rtrim(site_root(), '/') . '/' . ltrim($path, '/') . ($qs !== '' ? '?' . $qs : '');
}

/** Bangun path bersih dari halaman + aksi + id. */
function route_path(string $page, array &$params): string
{
    $transaksi = ['penjualan', 'pembelian', 'pemasukan', 'pengeluaran'];
    $keuangan = ['kas', 'piutang', 'hutang', 'modal'];
    $master = ['barang', 'kategori', 'supplier', 'pelanggan'];

    if (in_array($page, $transaksi, true)) {
        $base = '/transaksi/' . $page;
    } elseif (in_array($page, $keuangan, true)) {
        if ($page === 'kas' && ($params['tab'] ?? null) === 'saldo') {
            unset($params['tab']);
            $base = '/keuangan/kas/saldo';
        } else {
            $base = '/keuangan/' . $page;
        }
    } elseif (in_array($page, $master, true)) {
        $base = '/master/' . $page;
    } elseif ($page === 'laporan') {
        $base = '/laporan/' . ($params['tab'] ?? 'kas');
        unset($params['tab']);
    } else {
        $base = '/' . $page;
    }

    $action = $params['action'] ?? 'index';
    unset($params['action']);
    $id = $params['id'] ?? null;
    unset($params['id']);

    if ($action !== 'index') {
        $base .= '/' . $action;
        if ($id !== null) {
            $base .= '/' . $id;
        }
    }
    return $base;
}

/** URL untuk file statis (css, js, upload, gambar). */
function asset(string $path): string
{
    $prefix = defined('ROOT_ENTRY') ? '/public' : '';
    return site_root() . $prefix . '/' . ltrim($path, '/');
}

/**
 * Path bersih permintaan saat ini (contoh: "barang", "transaksi/penjualan").
 * Bebas PATH_INFO: diturunkan dari REQUEST_URI sehingga kompatibel dengan
 * Apache yang menonaktifkan AcceptPathInfo.
 */
function current_route_path(): string
{
    $pathInfo = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
    if ($pathInfo !== '') {
        return $pathInfo;
    }

    $uri = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));

    // Buang bagian dasar (folder project) bila REQUEST_URI mengikutinya
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($base !== '' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }

    // Buang sisa "public" / "index.php" bila docroot = root project
    $uri = preg_replace('#^/public#', '', $uri);
    $uri = preg_replace('#^/index\.php#', '', $uri);

    return trim($uri, '/');
}

/** Escape output HTML (anti XSS). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Format angka ke Rupiah, tanpa desimal. */
function rupiah($nilai): string
{
    $nilai = (float)$nilai;
    $prefix = $nilai < 0 ? '-' : '';
    return $prefix . 'Rp ' . number_format(abs($nilai), 0, ',', '.');
}

/** Format angka polos (dengan desimal bila perlu). */
function angka($nilai): string
{
    $nilai = (float)$nilai;
    if (floor($nilai) == $nilai) {
        return number_format($nilai, 0, ',', '.');
    }
    return rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',');
}

/** Format tanggal Indonesia. */
function tanggal($tanggal): string
{
    if (empty($tanggal) || $tanggal === '0000-00-00') {
        return '-';
    }
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    if (strlen($tanggal) > 10) {
        $tanggal = substr($tanggal, 0, 10);
    }
    list($y, $m, $d) = explode('-', $tanggal);
    if (isset($bulan[(int)$m])) {
        return (int)$d . ' ' . $bulan[(int)$m] . ' ' . $y;
    }
    return $tanggal;
}

function tanggal_waktu($datetime): string
{
    if (empty($datetime)) {
        return '-';
    }
    return tanggal(substr($datetime, 0, 10)) . ' ' . substr($datetime, 11, 5);
}

/** Ambil nilai dari $_GET/$_POST dengan default. */
function input(string $key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/** Ambil nilai old() untuk isi ulang form. */
function old(string $key, $default = '')
{
    return isset($_SESSION['old_input'][$key]) ? $_SESSION['old_input'][$key] : $default;
}

function flash_old(array $inputs): void
{
    $_SESSION['old_input'] = $inputs;
}

/** Buat CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Cetak input hidden CSRF. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verifikasi CSRF token dari POST. */
function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Simpan pesan flash. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Ambil & hapus semua flash. */
function flash_pull(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Tampilkan flash messages sebagai SweetAlert2 (dipanggil di layout/halaman). */
function flash_swal_scripts(): string
{
    $flashes = flash_pull();
    if (empty($flashes)) {
        return '<script>window.__kopsekWarnings = window.__kopsekWarnings || [];</script>';
    }
    $parts = [];
    foreach ($flashes as $f) {
        $icon = $f['type'] === 'success' ? 'success' : ($f['type'] === 'warning' ? 'warning' : 'error');
        $title = $f['type'] === 'success' ? 'Berhasil' : ($f['type'] === 'warning' ? 'Peringatan' : 'Gagal');
        $opt = [
            'icon' => $icon,
            'title' => $title,
            'text' => $f['message'],
        ];
        // Alert sukses: tutup otomatis
        if ($f['type'] === 'success') {
            $opt['timer'] = 2500;
            $opt['timerProgressBar'] = true;
            $opt['showConfirmButton'] = false;
            $opt['position'] = 'top-end';
            $opt['toast'] = true;
        } else {
            $opt['confirmButtonText'] = 'OK';
        }
        $parts[] = 'Swal.fire(' . json_encode($opt, JSON_UNESCAPED_UNICODE) . ');';
    }
    return '<script>document.addEventListener("DOMContentLoaded",function(){'
        . implode('', $parts)
        . '});</script>';
}

/** Validasi data POST sederhana. Kembalikan array error (kosong = lolos). */
function validate(array $rules): array
{
    $errors = [];
    foreach ($rules as $field => $rule) {
        $value = input($field);
        $name = str_replace(['_', '-'], ' ', $field);
        $checks = explode('|', $rule);
        foreach ($checks as $check) {
            if ($check === 'required' && (trim((string)$value) === '' || $value === null)) {
                $errors[$field] = ucfirst($name) . ' wajib diisi.';
            } elseif (strpos($check, 'min:') === 0) {
                $min = (float)substr($check, 4);
                if ($value !== '' && $value !== null && (float)$value < $min) {
                    $errors[$field] = ucfirst($name) . ' minimal ' . angka($min) . '.';
                }
            } elseif (strpos($check, 'max:') === 0) {
                $max = (float)substr($check, 4);
                if ($value !== '' && $value !== null && (float)$value > $max) {
                    $errors[$field] = ucfirst($name) . ' maksimal ' . angka($max) . '.';
                }
            } elseif ($check === 'numeric' && $value !== '' && $value !== null && !is_numeric($value)) {
                $errors[$field] = ucfirst($name) . ' harus berupa angka.';
            } elseif ($check === 'date' && $value !== '' && $value !== null) {
                $t = DateTime::createFromFormat('Y-m-d', $value);
                if (!$t || $t->format('Y-m-d') !== $value) {
                    $errors[$field] = ucfirst($name) . ' tanggal tidak valid.';
                }
            }
        }
    }
    return $errors;
}

/** Catat aktivitas ke audit log. */
function audit_log(string $aktivitas, string $detail = ''): void
{
    $user = $_SESSION['user'] ?? null;
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (user_id, username, aktivitas, detail, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $user['id'] ?? null,
        $user['username'] ?? 'system',
        $aktivitas,
        $detail,
        $_SERVER['REMOTE_ADDR'] ?? '',
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
    ]);
}

/** Ambil profil koperasi (di-cache per request). */
function koperasi_profile(bool $refresh = false): array
{
    static $profile = null;
    if ($profile === null || $refresh) {
        $stmt = db()->query('SELECT * FROM koperasi_profile WHERE id = 1');
        $profile = $stmt->fetch() ?: [];
    }
    return $profile;
}

/** Ambil satu pengaturan. */
function setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (!isset($cache[$key])) {
        $stmt = db()->prepare('SELECT `value` FROM settings WHERE `key` = ?');
        $stmt->execute([$key]);
        $cache[$key] = $stmt->fetchColumn() ?: $default;
    }
    return $cache[$key];
}

/** Ambil tahun ajaran aktif (format: 2024/2025). */
function tahun_ajaran_aktif(): string
{
    return setting('tahun_ajaran_aktif', date('Y') . '/' . (date('Y') + 1));
}

/** Ambil daftar tahun ajaran (3 tahun ke depan + 1 tahun lalu). */
function get_tahun_ajaran_list(): array
{
    $currentYear = (int)date('Y');
    $list = [];
    for ($i = -1; $i <= 3; $i++) {
        $y = $currentYear + $i;
        $list[] = "$y/" . ($y + 1);
    }
    return $list;
}

/** Generate kode barang otomatis (BRG00001, unik, tidak pernah reset). */
function kode_barang_otomatis(): string
{
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO number_counters (prefix, tanggal, last_number) VALUES ("BRG", "1900-01-01", 1)
         ON DUPLICATE KEY UPDATE last_number = last_number + 1'
    )->execute();
    $stmt = $pdo->prepare('SELECT last_number FROM number_counters WHERE prefix = "BRG" AND tanggal = "1900-01-01"');
    $stmt->execute();
    $n = (int)$stmt->fetchColumn();
    return 'BRG' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}

/** Digit cek EAN-13 (12 digit -> kembalikan 1 digit cek). */
function ean13_check_digit(string $digits12): string
{
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$digits12[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    return (string)((10 - ($sum % 10)) % 10);
}

/** Generate barcode EAN-13 otomatis (awalan GS1 Indonesia 899, unik). */
function barcode_otomatis(): string
{
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO number_counters (prefix, tanggal, last_number) VALUES ("BAR", "1900-01-01", 1)
         ON DUPLICATE KEY UPDATE last_number = last_number + 1'
    )->execute();
    $stmt = $pdo->prepare('SELECT last_number FROM number_counters WHERE prefix = "BAR" AND tanggal = "1900-01-01"');
    $stmt->execute();
    $n = (int)$stmt->fetchColumn();
    $base = '899' . str_pad((string)$n, 9, '0', STR_PAD_LEFT); // 12 digit
    return $base . ean13_check_digit($base);
}

/**
 * Pagination sederhana.
 * @return array{items:array,total:int,page:int,pages:int,perPage:int}
 */
function paginate_data(string $countSql, string $dataSql, array $params, string $orderSql, int $perPage = 20): array
{
    $pdo = db();
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $page = max(1, (int)($_GET['p'] ?? 1));
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare($dataSql . ' ' . $orderSql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset);
    $stmt->execute($params);

    return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
}

/** Cetak tombol pagination dengan query string terjaga. */
function pagination_links(array $pg, array $queryFilter = []): string
{
    if ($pg['pages'] <= 1) {
        return '';
    }
    $qs = [];
    foreach ($_GET as $k => $v) {
        if ($k !== 'p' && $v !== '') {
            $qs[$k] = $v;
        }
    }
    $qs = array_merge($qs, $queryFilter);

    if (isset($_GET['page'])) {
        // Mode fallback query lama
        $base = site_root() . '/index.php';
    } else {
        // Mode clean URL: pertahankan path bersih saat ini
        $base = site_root() . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    $make = function (int $p) use ($qs, $base) {
        return $base . '?' . http_build_query(array_merge($qs, ['p' => $p]));
    };

    $html = '<div class="flex items-center gap-1 mt-4 text-sm flex-wrap">';
    $html .= '<span class="text-slate-500 mr-2">Halaman ' . $pg['page'] . ' / ' . $pg['pages'] . ' (' . $pg['total'] . ' data)</span>';
    if ($pg['page'] > 1) {
        $html .= '<a class="px-3 py-1.5 rounded border border-slate-200 hover:bg-slate-50" href="' . $make($pg['page'] - 1) . '">Prev</a>';
    }
    $from = max(1, $pg['page'] - 2);
    $to = min($pg['pages'], $pg['page'] + 2);
    for ($i = $from; $i <= $to; $i++) {
        $active = $i === $pg['page'] ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-200 hover:bg-slate-50 text-slate-700';
        $html .= '<a class="px-3 py-1.5 rounded border ' . $active . '" href="' . $make($i) . '">' . $i . '</a>';
    }
    if ($pg['page'] < $pg['pages']) {
        $html .= '<a class="px-3 py-1.5 rounded border border-slate-200 hover:bg-slate-50" href="' . $make($pg['page'] + 1) . '">Next</a>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Generate nomor transaksi berurutan per hari, aman dari bentrok.
 * Contoh: PJ-20260819-0001
 */
function nomor_transaksi(string $prefix, string $tanggal = ''): string
{
    $tanggal = $tanggal !== '' ? date('Ymd', strtotime($tanggal)) : date('Ymd');
    $pdo = db();

// INSERT ... ON DUPLICATE KEY UPDATE bersifat atomik untuk counter.
    $pdo->prepare(
        'INSERT INTO number_counters (prefix, tanggal, last_number) VALUES (?, ?, 1)
         ON DUPLICATE KEY UPDATE last_number = last_number + 1'
    )->execute([$prefix, $tanggal]);

    $stmt = $pdo->prepare('SELECT last_number FROM number_counters WHERE prefix = ? AND tanggal = ?');
    $stmt->execute([$prefix, $tanggal]);
    $last = (int)$stmt->fetchColumn();

    return $prefix . '-' . $tanggal . '-' . str_pad((string)$last, 4, '0', STR_PAD_LEFT);
}

/** Redirect dengan pesan. */
function redirect(string $path, string $type = '', string $message = ''): void
{
    if ($type !== '' && $message !== '') {
        flash($type, $message);
    }
    // Dukung "page&action=x&id=y" menjadi query yang benar
    $parts = explode('&', $path);
    $page = array_shift($parts);
    $params = [];
    foreach ($parts as $p) {
        if (strpos($p, '=') !== false) {
            list($k, $v) = explode('=', $p, 2);
            $params[$k] = $v;
        }
    }
    header('Location: ' . url($page, $params));
    exit;
}

/** Tampilkan halaman error 403. */
function abort_forbidden(string $message = 'Anda tidak memiliki akses ke halaman ini.'): void
{
    http_response_code(403);
    require APP_ROOT . '/app/views/errors/403.php';
    exit;
}

function abort_notfound(string $message = 'Halaman tidak ditemukan.'): void
{
    http_response_code(404);
    require APP_ROOT . '/app/views/errors/404.php';
    exit;
}

/**
 * Unduh data sebagai file Excel (.xls, kompatibel Excel) tanpa library.
 * $columns = [kunci => label]; $rows = array baris; $moneyCols = daftar kunci angka rupiah.
 */
function download_excel(string $title, array $columns, array $rows, array $moneyCols = [], string $periode = ''): void
{
    $nama = str_replace(' ', '_', strtolower($title));
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nama . '_' . date('Ymd') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF"; // BOM agar Excel membaca UTF-8
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"><head><meta charset="utf-8"><style>
        table{border-collapse:collapse;} td,th{border:1px solid #999;padding:4px 8px;font-size:12px;} th{background:#eee;}</style></head><body>';
    echo '<h3>' . e($title) . '</h3>';
    if ($periode !== '') {
        echo '<p>' . e($periode) . '</p>';
    }
    echo '<table><thead><tr>';
    foreach ($columns as $label) {
        echo '<th>' . e($label) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach (array_keys($columns) as $key) {
            $val = $row[$key] ?? '';
            if (in_array($key, $moneyCols, true)) {
                $val = number_format((float)$val, 0, ',', '.');
            }
            echo '<td>' . e($val) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

/** Format durasi lalu lintas file untuk helper upload. */
function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 3) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, $i > 1 ? 1 : 0) . ' ' . $units[$i];
}

/** Status piutang/hutang dari jumlah dibayar & sisa. */
function status_tagihan(float $dibayar, float $sisa): array
{
    if ($sisa <= 0) {
        return ['Lunas', 'bg-emerald-100 text-emerald-700'];
    }
    if ($dibayar > 0) {
        return ['Sebagian', 'bg-amber-100 text-amber-700'];
    }
    return ['Belum Lunas', 'bg-red-100 text-red-700'];
}

/** Ikon SVG inline (tanpa dependensi eksternal). */
function icon(string $name, string $class = 'w-5 h-5'): string
{
    $icons = [
        'dashboard'    => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'cart'         => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
        'package'      => '<path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22.08V12"/>',
        'trending-up'  => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        'trending-down'=> '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>',
        'wallet'       => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
        'bank'         => '<path d="M3 9 12 2l9 7"/><path d="M21 9v10"/><path d="M3 9v10"/><path d="M5 12h14"/><path d="M7 12v7"/><path d="M11 12v7"/><path d="M15 12v7"/><path d="M19 12v7"/><path d="M3 22h18"/>',
        'cash'         => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
        'box'          => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22.08V12"/>',
        'tag'          => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'truck'        => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'truck-fast'   => '<path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5"/><path d="M14 17h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/><path d="M9 6h3"/>',
        'account-group'=> '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'account'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'account-tie'  => '<path d="M16 19h6"/><path d="M16 21h2"/><path d="M2 21h6"/><path d="M2 19h6"/><path d="M6 21v-2"/><path d="M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M6 21c0-2.5 2.5-4 6-4s6 1.5 6 4"/>',
        'account-cog'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h2"/><circle cx="18" cy="18" r="2.5"/><path d="M18 13v1"/><path d="M18 23v-1"/><path d="M13.5 15.5l.7.7"/><path d="M21.8 19.8l.7.7"/><path d="M13.5 20.5l.7-.7"/><path d="M21.8 16.2l.7-.7"/>',
        'scale'        => '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
        'scale-balance' => '<path d="m12 3 8 9"/><path d="m4 12 8-9"/><path d="M12 3v18"/><path d="M12 12l-2 3h4z"/><path d="M5 21h14"/><path d="M8 18h8"/>',
        'safe'         => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M12 9v1"/><path d="M12 15v-1"/><path d="M7 5V3"/><path d="M17 5V3"/><path d="M17 21v-2"/><path d="M7 21v-2"/>',
        'file-chart'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 17v-2"/><path d="M12 17v-5"/><path d="M15 17v-3"/>',
        'receipt'      => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/>',
        'chart-line'   => '<path d="M3 3v18h18"/><path d="M7 13l3-3 3 3 5-5"/>',
        'chart-arc'    => '<path d="M3 3v18h18"/><path d="M7 15v3"/><path d="M12 11v7"/><path d="M17 7v11"/>',
        'bank-transfer'=> '<rect x="2" y="10" width="20" height="10" rx="2"/><path d="M6 14h.01M12 14h.01"/><path d="M22 10V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v4"/>',
        'inventory'    => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22.08V12"/><path d="m7 4 10 6"/>',
        'calendar-month'=> '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M8 15h.01M12 15h.01M16 15h.01M8 19h.01M12 19h.01M16 19h.01"/>',
        'calendar-range'=> '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M8 15h.01M12 15h.01M16 15h.01M8 19h.01M12 19h.01M16 19h.01"/>',
        'school'       => '<path d="M14 22v-4a2 2 0 1 0-4 0v4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M18 5v17"/><path d="m4 6 8-4 8 4"/><path d="M18 10a2 2 0 0 1-4 0 2 2 0 0 1-4 0 2 2 0 0 1-4 0"/>',
        'cog'          => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>',
        'database'     => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        'history'      => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
        'key'          => '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        'logout'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'menu'         => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/>',
        'search'       => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'printer'      => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'download'     => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'upload'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'plus'         => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'edit'         => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/>',
        'trash'        => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'x'            => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'check'        => '<polyline points="20 6 9 17 4 12"/>',
        'alert'        => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'eye'          => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'file'         => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'refresh'      => '<polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>',
        'chevron-left' => '<polyline points="15 18 9 12 15 6"/>',
        'chevron-right'=> '<polyline points="9 18 15 12 9 6"/>',
        'money'        => '<circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="M16 10c0-1.5-1.79-2.5-4-2.5s-4 1-4 2.5 1.79 2.5 4 2.5 4 1 4 2.5-1.79 2.5-4 2.5-4-1-4-2.5"/>',
        'power'        => '<path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/>',
        'sliders'      => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
        'calculator'   => '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="12" x2="8" y2="12.01"/><line x1="12" y1="12" x2="12" y2="12.01"/><line x1="16" y1="12" x2="16" y2="12.01"/><line x1="8" y1="16" x2="8" y2="16.01"/><line x1="12" y1="16" x2="12" y2="16.01"/><line x1="16" y1="16" x2="16" y2="16.01"/><line x1="8" y1="20" x2="8" y2="20.01"/><line x1="12" y1="20" x2="12" y2="20.01"/><line x1="16" y1="20" x2="16" y2="20.01"/>',
        'filter'       => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
    ];

    if (!isset($icons[$name])) {
        return '';
    }
    return '<svg class="' . e($class) . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">' . $icons[$name] . '</svg>';
}

/** ============================================================
 * Helper unggah & kelola bukti transaksi (attachments)
 * ============================================================ */

/**
 * Simpan file bukti dari $_FILES[$name].
 * Validasi tipe & ukuran. Nama file diacak (aman).
 * @return array{attachment:array|null,error:string|null}
 */
function save_attachment(string $relatedType, int $relatedId, string $inputName = 'bukti'): array
{
    if (empty($_FILES[$inputName]['name']) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['attachment' => null, 'error' => null];
    }
    $file = $_FILES[$inputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['attachment' => null, 'error' => 'Gagal mengunggah file.'];
    }
    if ($file['size'] > APP_MAX_UPLOAD) {
        return ['attachment' => null, 'error' => 'Ukuran file maksimal ' . format_bytes(APP_MAX_UPLOAD) . '.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
    if (!isset($allowed[$mime])) {
        return ['attachment' => null, 'error' => 'Format file harus JPG, JPEG, PNG, atau PDF.'];
    }
    $ext = $allowed[$mime];
    $stored = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . DIRECTORY_SEPARATOR . $stored)) {
        return ['attachment' => null, 'error' => 'Gagal menyimpan file.'];
    }

    db()->prepare(
        'INSERT INTO attachments (related_type, related_id, stored_name, original_name, mime, size, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$relatedType, $relatedId, $stored, basename($file['name']), $mime, $file['size'], $_SESSION['user']['id'] ?? null]);

    return ['attachment' => ['stored_name' => $stored, 'original_name' => basename($file['name'])], 'error' => null];
}

function attachments_of(string $relatedType, int $relatedId): array
{
    $stmt = db()->prepare('SELECT * FROM attachments WHERE related_type = ? AND related_id = ? ORDER BY id DESC');
    $stmt->execute([$relatedType, $relatedId]);
    return $stmt->fetchAll();
}

function delete_attachment(int $attachmentId, string $relatedType): void
{
    $stmt = db()->prepare('SELECT * FROM attachments WHERE id = ? AND related_type = ?');
    $stmt->execute([$attachmentId, $relatedType]);
    $att = $stmt->fetch();
    if ($att) {
        $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $att['stored_name'];
        if (file_exists($path)) {
            @unlink($path);
        }
        db()->prepare('DELETE FROM attachments WHERE id = ?')->execute([$attachmentId]);
        audit_log('HAPUS BUKTI', $att['original_name']);
    }
}

/** URL untuk melihat bukti. */
function attachment_url(array $att): string
{
    return asset('uploads/' . $att['stored_name']) . '?n=' . rawurlencode($att['original_name']);
}

/** Tampilkan tombol/link bukti dari daftar attachments. */
function attachment_badges(array $atts): string
{
    if (empty($atts)) {
        return '<span class="text-slate-400 text-xs">Tidak ada</span>';
    }
    $html = '<div class="flex flex-wrap gap-2">';
    foreach ($atts as $a) {
        $icon = strpos($a['mime'], 'pdf') !== false ? 'file' : 'eye';
        $target = strpos($a['mime'], 'pdf') !== false ? '_blank' : '_blank';
        $html .= '<a href="' . e(attachment_url($a)) . '" target="' . $target . '" class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">'
            . icon($icon, 'w-3.5 h-3.5') . ' ' . e($a['original_name']) . '</a>';
    }
    $html .= '</div>';
    return $html;
}
