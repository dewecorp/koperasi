<?php

require_once APP_ROOT . '/app/core/Controller.php';

class PenggunaController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator']);
        $pdo = db();
        $q = trim(input('q', ''));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where = '(u.username LIKE ? OR u.name LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }
        $pg = paginate_data(
            'SELECT COUNT(*) FROM users u WHERE ' . $where,
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE ' . $where,
            $params,
            'ORDER BY u.id ASC',
            20
        );
        $roles = $pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();
        $this->render('pengguna/index', [
            'pageTitle' => 'Pengguna',
            'pg' => $pg,
            'q' => $q,
            'roles' => $roles,
        ]);
    }

    public function store(): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pengguna');
        }
        $username = trim(input('username', ''));
        $name = trim(input('name', ''));
        $roleId = (int)input('role_id', 0);
        $password = input('password', '');

        if ($username === '' || $name === '' || $roleId < 1) {
            flash('error', 'Username, nama, dan peran wajib diisi.');
            redirect('pengguna');
        }
        if (strlen($password) < 6) {
            flash('error', 'Password minimal 6 karakter.');
            redirect('pengguna');
        }

        $pdo = db();
        $dup = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $dup->execute([$username]);
        if ($dup->fetch()) {
            flash('error', 'Username "' . $username . '" sudah digunakan.');
            redirect('pengguna');
        }

        $pdo->prepare(
            'INSERT INTO users (role_id, username, name, password_hash, must_change_password, is_active) VALUES (?, ?, ?, ?, 1, 1)'
        )->execute([$roleId, $username, $name, password_hash($password, PASSWORD_DEFAULT)]);

        audit_log('TAMBAH PENGGUNA', $username);
        flash('success', 'Pengguna ditambahkan. Password awal wajib diganti saat login pertama.');
        redirect('pengguna');
    }

    public function update(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pengguna');
        }
        $name = trim(input('name', ''));
        $roleId = (int)input('role_id', 0);
        $isActive = input('is_active', '1') === '1' ? 1 : 0;
        $password = input('password', '');

        if ($name === '' || $roleId < 1) {
            flash('error', 'Nama dan peran wajib diisi.');
            redirect('pengguna');
        }

        $pdo = db();
        $target = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $target->execute([$id]);
        $user = $target->fetch();
        if (!$user) {
            abort_notfound('Pengguna tidak ditemukan.');
        }

        // Jangan biarkan admin menonaktifkan diri sendiri
        if ((int)$id === (int)$_SESSION['user']['id'] && $isActive === 0) {
            flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            redirect('pengguna');
        }

        $pdo->prepare('UPDATE users SET name=?, role_id=?, is_active=? WHERE id=?')->execute([$name, $roleId, $isActive, $id]);

        if ($password !== '') {
            if (strlen($password) < 6) {
                flash('error', 'Password baru minimal 6 karakter.');
                redirect('pengguna');
            }
            $pdo->prepare('UPDATE users SET password_hash=?, must_change_password=1 WHERE id=?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
        }

        audit_log('UBAH PENGGUNA', 'ID: ' . $id . ' (' . $user['username'] . ')');
        flash('success', 'Pengguna diperbarui.');
        redirect('pengguna');
    }

    public function destroy(?string $id = null): void
    {
        $this->guard(['Administrator']);
        if (!csrf_verify()) {
            flash('error', 'Token keamanan tidak valid.');
            redirect('pengguna');
        }
        $pdo = db();
        if ((int)$id === (int)$_SESSION['user']['id']) {
            flash('error', 'Anda tidak dapat menghapus akun sendiri.');
            redirect('pengguna');
        }
        // Cegah menghapus admin terakhir
        $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name="Administrator" AND u.is_active=1')->fetchColumn();
        $target = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?');
        $target->execute([$id]);
        $user = $target->fetch();
        if (!$user) {
            abort_notfound('Pengguna tidak ditemukan.');
        }
        if ($user['role_name'] === 'Administrator' && $adminCount <= 1) {
            flash('error', 'Tidak dapat menghapus administrator terakhir.');
            redirect('pengguna');
        }

        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        audit_log('HAPUS PENGGUNA', 'ID: ' . $id . ' (' . $user['username'] . ')');
        flash('success', 'Pengguna dihapus.');
        redirect('pengguna');
    }
}
