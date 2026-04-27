<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\FileUploadService;
use App\Models\KostModel;

final class AdminKostController extends Controller
{
    private KostModel $kostModel;
    private FileUploadService $uploader;

    public function __construct()
    {
        $this->kostModel = new KostModel();
        $this->uploader = new FileUploadService();
    }

    public function create(): void
    {
        $this->requireAdmin();

        if ($this->isPost() && isset($_POST['simpan'])) {
            try {
                $filename = $this->uploader->upload($_FILES['foto'] ?? null);

                $this->kostModel->create([
                    'nama_kost' => trim((string) $_POST['nama_kost']),
                    'alamat' => trim((string) $_POST['alamat']),
                    'deskripsi' => trim((string) ($_POST['deskripsi'] ?? '')),
                    'foto_kost' => $filename,
                ]);

                set_flash('success', 'Data Kost Berhasil Ditambahkan');
                redirect_to('/admin/dashboard');
            } catch (\Throwable $throwable) {
                set_flash('error', $throwable->getMessage());
                redirect_to('/admin/kost/create');
            }
        }

        $this->render('admin/forms/kost-create');
    }

    public function edit(): void
    {
        $this->requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $kost = $this->kostModel->findById($id);

        if ($kost === null) {
            set_flash('error', 'Data kost tidak ditemukan.');
            redirect_to('/admin/dashboard');
        }

        if ($this->isPost() && isset($_POST['update'])) {
            try {
                $payload = [
                    'nama_kost' => trim((string) $_POST['nama_kost']),
                    'alamat' => trim((string) $_POST['alamat']),
                    'deskripsi' => trim((string) ($_POST['deskripsi'] ?? '')),
                ];

                $filename = $this->uploader->upload($_FILES['foto'] ?? null, false);
                if ($filename !== null) {
                    $payload['foto_kost'] = $filename;
                }

                $this->kostModel->update($id, $payload);
                set_flash('success', 'Data Berhasil Diupdate');
                redirect_to('/admin/dashboard');
            } catch (\Throwable $throwable) {
                set_flash('error', $throwable->getMessage());
                redirect_to('/admin/kost/edit?id=' . $id);
            }
        }

        $this->render('admin/forms/kost-edit', [
            'kost' => $kost,
        ]);
    }

    public function delete(): void
    {
        $this->requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $this->kostModel->delete($id);
        set_flash('success', 'Data Terhapus');
        redirect_to('/admin/dashboard');
    }
}
