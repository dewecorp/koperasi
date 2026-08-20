<?php

/**
 * Controller dasar.
 * - require_login() default untuk semua halaman kecuali AuthController.
 * - render view dengan variabel yang di-ekstrak.
 */

require_once APP_ROOT . '/app/core/Model.php';

class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data);
        $viewFile = APP_ROOT . '/app/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException('View tidak ditemukan: ' . $viewFile);
        }
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = APP_ROOT . '/app/views/layouts/' . $layout . '.php';
        require $layoutFile;
    }

    /** Render tanpa layout (halaman cetak). */
    protected function renderPrint(string $view, array $data = []): void
    {
        extract($data);
        require APP_ROOT . '/app/views/' . $view . '.php';
    }

    protected function renderJson(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /** Daftar role yang boleh mengakses controller ini. Default: semua yang login. */
    protected function guard(array $roles = ['Administrator', 'Bendahara', 'Petugas']): void
    {
        require_login();
        require_role($roles);
    }

    protected function error(string $message): void
    {
        flash('error', $message);
    }

    protected function ok(string $message): void
    {
        flash('success', $message);
    }
}
