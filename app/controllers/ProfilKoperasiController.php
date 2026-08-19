<?php

require_once APP_ROOT . '/app/core/Controller.php';

class ProfilKoperasiController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator']);
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                flash('error', 'Token keamanan tidak valid.');
                redirect('profil');
            }
            $data = [
                'nama_koperasi' => trim(input('nama_koperasi', '')),
                'nama_sekolah' => trim(input('nama_sekolah', '')),
                'alamat' => trim(input('alamat', '')),
                'telepon' => trim(input('telepon', '')),
                'email' => trim(input('email', '')),
                'nama_ketua' => trim(input('nama_ketua', '')),
                'nama_bendahara' => trim(input('nama_bendahara', '')),
            ];
            if ($data['nama_koperasi'] === '') {
                flash('error', 'Nama koperasi wajib diisi.');
                redirect('profil');
            }

            // Upload logo (opsional)
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $logo = $this->uploadLogo();
                if ($logo === null) {
                    redirect('profil');
                }
                $data['logo'] = $logo;
            }

            $pdo->prepare(
                'UPDATE koperasi_profile SET nama_koperasi=?, nama_sekolah=?, alamat=?, telepon=?, email=?, logo=COALESCE(?, logo), nama_ketua=?, nama_bendahara=? WHERE id=1'
            )->execute([
                $data['nama_koperasi'], $data['nama_sekolah'], $data['alamat'],
                $data['telepon'], $data['email'], $data['logo'] ?? null,
                $data['nama_ketua'], $data['nama_bendahara'],
            ]);

            audit_log('UBAH PROFIL KOPERASI', $data['nama_koperasi']);
            flash('success', 'Profil koperasi disimpan.');
            redirect('profil');
        }

        $this->render('profil/index', [
            'pageTitle' => 'Profil Koperasi',
            'profile' => koperasi_profile(true),
        ]);
    }

    private function uploadLogo(): ?string
    {
        $file = $_FILES['logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Gagal mengunggah logo.');
            return null;
        }
        if ($file['size'] > APP_MAX_UPLOAD) {
            flash('error', 'Ukuran logo maksimal 2 MB.');
            return null;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        if (!isset($allowed[$mime])) {
            flash('error', 'Format logo harus JPG, PNG, atau GIF.');
            return null;
        }
        $ext = $allowed[$mime];
        $name = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0775, true);
        }
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . DIRECTORY_SEPARATOR . $name)) {
            flash('error', 'Gagal menyimpan logo.');
            return null;
        }
        audit_log('UPLOAD LOGO', $name);
        return $name;
    }
}
