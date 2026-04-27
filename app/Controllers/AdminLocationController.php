<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\KostModel;

final class AdminLocationController extends Controller
{
    private KostModel $kostModel;

    public function __construct()
    {
        $this->kostModel = new KostModel();
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

        if ($this->isPost() && isset($_POST['simpan'])) {
            $this->kostModel->updateLocation(
                $id,
                trim((string) $_POST['latitude']),
                trim((string) $_POST['longitude'])
            );

            set_flash('success', 'Lokasi berhasil disimpan!');
            redirect_to('/admin/dashboard');
        }

        $this->render('admin/forms/location-edit', [
            'kost' => $kost,
        ]);
    }
}
