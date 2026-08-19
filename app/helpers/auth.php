<?php

/**
 * ============================================================
 * Autentikasi & otorisasi berbasis session
 * Semua pemeriksaan dilakukan di server.
 * ============================================================
 */

/** Mulai session dengan pengaturan aman. */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(APP_SESSION_NAME);
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    // Regenerasi ID session berkala untuk mencegah fixation
    if (empty($_SESSION['last_regenerate'])) {
        $_SESSION['last_regenerate'] = time();
    } elseif (time() - $_SESSION['last_regenerate'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regenerate'] = time();
    }
}

/** Cek apakah pengguna sudah login. */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

/** Ambil data pengguna aktif dari DB (segar setiap kali dibutuhkan). */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.is_active = 1'
        );
        $stmt->execute([$_SESSION['user']['id']]);
        $user = $stmt->fetch() ?: null;
        if ($user === null) {
            // akun dinonaktifkan / dihapus
            unset($_SESSION['user']);
        }
    }
    return $user;
}

/** Wajib login; jika belum, arahkan ke halaman login. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login');
    }
}

/** Cek peran minimum. $roles = array nama role yang diizinkan. */
function require_role(array $roles): void
{
    $user = current_user();
    if (!$user || !in_array($user['role_name'], $roles, true)) {
        abort_forbidden();
    }
}

/** Cek apakah user punya peran tertentu. */
function has_role(string $role): bool
{
    $user = current_user();
    return $user && $user['role_name'] === $role;
}
