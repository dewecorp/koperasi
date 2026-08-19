<?php

require_once APP_ROOT . '/app/core/Controller.php';

class LogController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator']);
        $pdo = db();
        $q = trim(input('q', ''));
        $dari = input('dari', '');
        $sampai = input('sampai', '');

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(username LIKE ? OR aktivitas LIKE ? OR detail LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($dari !== '' && $sampai !== '') {
            $where[] = 'DATE(created_at) BETWEEN ? AND ?';
            $params = array_merge($params, [$dari, $sampai]);
        }
        $whereSql = implode(' AND ', $where);

        $pg = paginate_data(
            'SELECT COUNT(*) FROM audit_logs WHERE ' . $whereSql,
            'SELECT * FROM audit_logs WHERE ' . $whereSql,
            $params,
            'ORDER BY id DESC',
            30
        );

        $this->render('log/index', [
            'pageTitle' => 'Log Aktivitas',
            'pg' => $pg,
            'q' => $q,
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }
}