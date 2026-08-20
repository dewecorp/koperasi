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

        // Jumlah aktivitas (total & per user)
        $totalAktivitas = (int)$pg['total'];
        $jmlUser = (int)$pdo->query('SELECT COUNT(DISTINCT username) FROM audit_logs')->fetchColumn();
        $perUser = $pdo->query(
            'SELECT username, COUNT(*) AS jml FROM audit_logs GROUP BY username ORDER BY jml DESC LIMIT 8'
        )->fetchAll();
        $perJenis = $pdo->query(
            'SELECT aktivitas, COUNT(*) AS jml FROM audit_logs GROUP BY aktivitas ORDER BY jml DESC LIMIT 8'
        )->fetchAll();

        $this->render('log/index', [
            'pageTitle' => 'Log Aktivitas',
            'pg' => $pg,
            'q' => $q,
            'dari' => $dari,
            'sampai' => $sampai,
            'totalAktivitas' => $totalAktivitas,
            'jmlUser' => $jmlUser,
            'perUser' => $perUser,
            'perJenis' => $perJenis,
        ]);
    }
}