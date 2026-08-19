<?php

require_once APP_ROOT . '/app/core/Controller.php';

class AuthController extends Controller
{
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

        $stmt = db()->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.username = ?'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            audit_log('LOGIN GAGAL', 'Username: ' . $username);
            flash('error', 'Username atau password salah.');
            redirect('login');
        }

        if ((int)$user['is_active'] !== 1) {
            audit_log('LOGIN GAGAL', 'Akun nonaktif: ' . $username);
            flash('error', 'Akun Anda dinonaktifkan. Hubungi administrator.');
            redirect('login');
        }

        // Simpan data ringkas di session
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role_id' => (int)$user['role_id'],
            'role_name' => $user['role_name'],
        ];

        audit_log('LOGIN', 'Login berhasil');

        if ((int)$user['must_change_password'] === 1) {
            flash('warning', 'Anda diwajibkan mengganti password sebelum melanjutkan.');
            flash('success', 'Login berhasil. Selamat datang, ' . $user['name'] . '.');
            redirect('password');
        }

        flash('success', 'Login berhasil. Selamat datang, ' . $user['name'] . '.');
        redirect('dashboard');
    }

    public function logout(): void
    {
        require_login();
        audit_log('LOGOUT', 'Logout');
        session_unset();
        session_destroy();
        // Buka session baru agar pesan di halaman login tetap tampil
        start_session();
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
