<?php

require_once APP_ROOT . '/app/core/Controller.php';

class PengaturanController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator']);
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                flash('error', 'Token keamanan tidak valid.');
                redirect('pengaturan');
            }
            $keys = [
                'allow_negative_cash' => input('allow_negative_cash', '0') === '1' ? '1' : '0',
                'allow_negative_stock' => input('allow_negative_stock', '0') === '1' ? '1' : '0',
                'saldo_minimum_cash' => (string)max(0, (float)input('saldo_minimum_cash', 0)),
            ];
            foreach ($keys as $key => $value) {
                $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')
                    ->execute([$key, $value]);
            }
            audit_log('UBAH PENGATURAN', json_encode($keys));
            flash('success', 'Pengaturan disimpan.');
            redirect('pengaturan');
        }

        $this->render('pengaturan/index', [
            'pageTitle' => 'Pengaturan Aplikasi',
            'set' => [
                'allow_negative_cash' => setting('allow_negative_cash', '0'),
                'allow_negative_stock' => setting('allow_negative_stock', '0'),
                'saldo_minimum_cash' => setting('saldo_minimum_cash', '500000'),
            ],
        ]);
    }
}