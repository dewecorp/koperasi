<?php

require_once APP_ROOT . '/app/core/Controller.php';

class AuthController extends Controller
{
    /** Maksimal percobaan gagal sebelum dikunci. */
    private const MAX_ATTEMPTS = 5;
    /** Durasi kunci (menit). */
    private const LOCK_MINUTES = 15;

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->authenticate();
            return;
        }
        if (is_logged_in()) {
            redirect('dashboard');
        }
        $this->render('auth/login', [], 'auth');
    }

    private function authenticate(): void
    {
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
            redirect('login');
        }

        $username = trim(input('username', ''));
        $password = input('password', '');

        if ($username === '' || $password === '') {
            flash_old(['username' => $username]);
            flash('error', 'Username dan password wajib diisi.');
            redirect('login');
        }

        // Anti brute force: cek percobaan gagal terakhir
        $sisa = $this->throttleSisaKunci($username);
        if ($sisa > 0) {
            audit_log('LOGIN DIBLOKIR', 'Username: ' . $username);
            flash('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ceil($sisa / 60) . ' menit.');
            redirect('login');
        }

        $stmt = db()->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.username = ?'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->catatPercobaan($username, false);
            audit_log('LOGIN GAGAL', 'Username: ' . $username);
            flash('error', 'Username atau password salah.');
            redirect('login');
        }

        if ((int)$user['is_active'] !== 1) {
            $this->catatPercobaan($username, false);
            audit_log('LOGIN GAGAL', 'Akun nonaktif: ' . $username);
            flash('error', 'Akun Anda dinonaktifkan. Hubungi administrator.');
            redirect('login');
        }

        // Login berhasil: reset jumlah percobaan
        $this->catatPercobaan($username, true);

        // Simpan data ringkas di session
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role_id' => (int)$user['role_id'],
            'role_name' => $user['role_name'],
        ];
        $_SESSION['last_activity'] = time();

        audit_log('LOGIN', 'Login berhasil');

        if ((int)$user['must_change_password'] === 1) {
            flash('warning', 'Anda diwajibkan mengganti password sebelum melanjutkan.');
            flash('success', 'Login berhasil. Selamat datang, ' . $user['name'] . '.');
            redirect('password');
        }

        flash('success', 'Login berhasil. Selamat datang, ' . $user['name'] . '.');
        redirect('dashboard');
    }

    /** Sisa detik kunci bila percobaan gagal melebihi batas; 0 = boleh coba. */
    private function throttleSisaKunci(string $username): int
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE success = 0 AND attempt_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
             AND (username = ? OR ip = ?)'
        );
        $stmt->execute([self::LOCK_MINUTES, $username, $ip]);
        $jml = (int)$stmt->fetchColumn();

        if ($jml < self::MAX_ATTEMPTS) {
            return 0;
        }
        // Ambil waktu percobaan terakhir untuk hitung sisa waktu kunci
        $stmt = $pdo->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MAX(attempt_at), INTERVAL ? MINUTE))
             FROM login_attempts WHERE success = 0 AND attempt_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE) AND (username = ? OR ip = ?)'
        );
        $stmt->execute([self::LOCK_MINUTES, self::LOCK_MINUTES, $username, $ip]);
        return max(0, (int)$stmt->fetchColumn());
    }

    private function catatPercobaan(string $username, bool $sukses): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        db()->prepare(
            'INSERT INTO login_attempts (username, ip, attempt_at, success) VALUES (?, ?, NOW(), ?)'
        )->execute([$username, $ip, $sukses ? 1 : 0]);

        // Bersihkan data lama (lebih dari 1 jam) agar tabel tidak membengkak
        db()->exec('DELETE FROM login_attempts WHERE attempt_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    }

    public function logout(): void
    {
        require_login();
        audit_log('LOGOUT', 'Logout');
        destroy_session();
        flash('success', 'Anda telah berhasil keluar.');
        redirect('login');
    }

    public function password(?string $id = null): void
    {
        require_login();
        $user = current_user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                flash('error', 'Token keamanan tidak valid.');
                redirect('password');
            }

            $current = input('current_password', '');
            $new = input('new_password', '');
            $confirm = input('confirm_password', '');

            if (!password_verify($current, $user['password_hash'])) {
                flash('error', 'Password lama salah.');
                redirect('password');
            }
            if (strlen($new) < 6) {
                flash('error', 'Password baru minimal 6 karakter.');
                redirect('password');
            }
            if ($new !== $confirm) {
                flash('error', 'Konfirmasi password tidak cocok.');
                redirect('password');
            }

            $hash = password_hash($new, PASSWORD_DEFAULT);
            db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')
                ->execute([$hash, $user['id']]);

            audit_log('GANTI PASSWORD', 'User: ' . $user['username']);
            flash('success', 'Password berhasil diganti.');
            redirect('dashboard');
        }

        $this->render('auth/password', ['user' => $user]);
    }
}
