<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/services/FinanceService.php';

class KasController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator', 'Bendahara']);

        if (input('tab') === 'saldo') {
            $this->saldoAwalPage();
            return;
        }
        $this->bukuKasPage();
    }

    private function saldoAwalPage(): void
    {
        $this->guard(['Administrator']);
        $fin = new FinanceService();
        $tahunAjaran = tahun_ajaran_aktif();
        $saldoAwal = $fin->saldoAwalRow($tahunAjaran);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                flash('error', 'Token keamanan tidak valid.');
                redirect('kas&tab=saldo');
            }
            $tanggal = input('tanggal', date('Y-m-d'));
            $nominal = (float)input('nominal', 0);
            $keterangan = trim(input('keterangan', ''));

            $errors = validate(['tanggal' => 'date', 'nominal' => 'numeric|min:0']);
            if ($errors) {
                foreach ($errors as $e) flash('error', $e);
                redirect('kas&tab=saldo');
            }

            try {
                $sebelum = $saldoAwal ? $saldoAwal['nominal'] : 0;
                $fin->setSaldoAwal($tanggal, $nominal, $keterangan, $tahunAjaran);
                audit_log('UBAH SALDO AWAL', 'dari ' . rupiah($sebelum) . ' menjadi ' . rupiah($nominal));
                flash('success', 'Saldo awal disimpan.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
            }
            redirect('kas&tab=saldo');
        }

        $this->render('kas/saldo_awal', [
            'pageTitle' => 'Saldo Awal',
            'tahunAjaran' => $tahunAjaran,
            'saldoAwal' => $saldoAwal,
            'saldoKas' => $fin->saldoKas(null, $tahunAjaran),
        ]);
    }

    private function bukuKasPage(): void
    {
        $fin = new FinanceService();
        $tahunAjaran = tahun_ajaran_aktif();
        $range = input('range', 'tahun');
        $dari = '';
        $sampai = '';

        switch ($range) {
            case 'hari':
                $dari = $sampai = date('Y-m-d');
                break;
            case 'minggu':
                $dari = date('Y-m-d', strtotime('monday this week'));
                $sampai = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'bulan':
                $dari = date('Y-m-01');
                $sampai = date('Y-m-t');
                break;
            case 'custom':
                $dari = input('dari', date('Y-m-01'));
                $sampai = input('sampai', date('Y-m-d'));
                break;
            default: // tahun
                $dari = date('Y-01-01');
                $sampai = date('Y-12-31');
        }

        $buku = $fin->bukuKas($dari, $sampai, $tahunAjaran);
        // Kas masuk termasuk saldo awal (baris pembuka buku kas)
        $totMasuk = array_sum(array_map(function ($r) { return ($r['jenis'] === 'masuk' || $r['jenis'] === 'saldo_awal') ? $r['nominal'] : 0; }, $buku['rows']));
        $totKeluar = array_sum(array_map(function ($r) { return $r['jenis'] === 'keluar' ? $r['nominal'] : 0; }, $buku['rows']));

        $this->render('kas/buku_kas', [
            'pageTitle' => 'Buku Kas',
            'tahunAjaran' => $tahunAjaran,
            'buku' => $buku,
            'range' => $range,
            'dari' => $dari,
            'sampai' => $sampai,
            'totMasuk' => $totMasuk,
            'totKeluar' => $totKeluar,
            'saldoAkhir' => $buku['saldo_akhir'],
        ]);
    }
}