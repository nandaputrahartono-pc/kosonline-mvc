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
            $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                set_flash('error', 'Koordinat latitude atau longitude tidak valid.');
                redirect_to('/admin/locations/edit?id=' . $id);
            }

            $this->kostModel->updateLocation(
                $id,
                (string) $latitude,
                (string) $longitude
            );

            set_flash('success', 'Lokasi berhasil disimpan!');
            redirect_to('/admin/dashboard');
        }

        $this->render('admin/forms/location-edit', [
            'kost' => $kost,
        ]);
    }
}
