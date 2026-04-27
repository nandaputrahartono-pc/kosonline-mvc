<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\KostModel;
use App\Models\PaymentModel;
use App\Models\RentalModel;
use App\Models\RoomModel;
use App\Models\UserModel;

final class AdminRoomController extends Controller
{
    private RoomModel $roomModel;
    private KostModel $kostModel;
    private UserModel $userModel;
    private RentalModel $rentalModel;
    private PaymentModel $paymentModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
        $this->kostModel = new KostModel();
        $this->userModel = new UserModel();
        $this->rentalModel = new RentalModel();
        $this->paymentModel = new PaymentModel();
    }

    public function create(): void
    {
        $this->requireAdmin();

        if ($this->isPost() && isset($_POST['simpan'])) {
            $db = Database::getInstance();
            $tenantId = (int) ($_POST['id_user'] ?? 0);

            if ($tenantId > 0 && $this->rentalModel->getActiveByUserIdOnly($tenantId) !== null) {
                set_flash('error', 'User yang dipilih masih terdaftar sebagai penghuni aktif di kamar lain.');
                redirect_to('/admin/rooms/create');
            }

            $db->beginTransaction();

            try {
                $roomId = $this->roomModel->create([
                    'id_kost' => (int) $_POST['id_kost'],
                    'nomor_kamar' => trim((string) $_POST['nomor_kamar']),
                    'lantai' => (int) $_POST['lantai'],
                    'fasilitas' => trim((string) ($_POST['fasilitas'] ?? '')),
                    'harga' => (float) $_POST['harga'],
                    'status' => $tenantId > 0 ? 'Terisi' : 'Tersedia',
                ]);

                if ($tenantId > 0) {
                    $this->rentalModel->createActive($tenantId, $roomId);
                }

                $db->commit();
                set_flash('success', 'Data Kamar Berhasil Ditambahkan');
                redirect_to('/admin/dashboard');
            } catch (\Throwable $throwable) {
                $db->rollback();
                set_flash('error', 'Gagal menambahkan kamar.');
                redirect_to('/admin/rooms/create');
            }
        }

        $this->render('admin/forms/room-create', [
            'kosts' => $this->kostModel->getAll(),
            'users' => $this->userModel->getAvailableForRental(),
        ]);
    }

    public function edit(): void
    {
        $this->requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $room = $this->roomModel->findById($id);

        if ($room === null) {
            set_flash('error', 'Data kamar tidak ditemukan.');
            redirect_to('/admin/dashboard');
        }

        $activeRental = $this->rentalModel->getActiveTenantByRoomId($id);
        $currentTenant = (int) ($activeRental['id_user'] ?? 0);

        if ($this->isPost() && isset($_POST['update'])) {
            $db = Database::getInstance();
            $newTenant = (int) ($_POST['id_user'] ?? 0);

            if (
                $newTenant > 0 &&
                $newTenant !== $currentTenant &&
                $this->rentalModel->getActiveByUserIdOnly($newTenant) !== null
            ) {
                set_flash('error', 'User yang dipilih masih terdaftar sebagai penghuni aktif di kamar lain.');
                redirect_to('/admin/rooms/edit?id=' . $id);
            }

            $db->beginTransaction();

            try {
                $this->roomModel->update($id, [
                    'id_kost' => (int) $_POST['id_kost'],
                    'nomor_kamar' => trim((string) $_POST['nomor_kamar']),
                    'lantai' => (int) $_POST['lantai'],
                    'fasilitas' => trim((string) ($_POST['fasilitas'] ?? '')),
                    'harga' => (float) $_POST['harga'],
                    'status' => $newTenant > 0 ? 'Terisi' : 'Tersedia',
                ]);

                if ($newTenant !== $currentTenant) {
                    if ($activeRental !== null) {
                        $this->rentalModel->stopActiveByRentalId((int) $activeRental['id_sewa']);
                    }

                    if ($newTenant > 0) {
                        $this->rentalModel->createActive($newTenant, $id);
                    }
                }

                $db->commit();
                set_flash('success', 'Data Kamar & Penghuni Berhasil Diupdate');
                redirect_to('/admin/dashboard');
            } catch (\Throwable $throwable) {
                $db->rollback();
                set_flash('error', 'Gagal mengupdate kamar.');
                redirect_to('/admin/rooms/edit?id=' . $id);
            }
        }

        $this->render('admin/forms/room-edit', [
            'room' => $room,
            'kosts' => $this->kostModel->getAll(),
            'users' => $this->userModel->getAvailableForRental($currentTenant),
            'currentTenant' => $currentTenant,
        ]);
    }

    public function delete(): void
    {
        $this->requireAdmin();

        $roomId = (int) ($_GET['id'] ?? 0);
        $db = Database::getInstance();

        $db->beginTransaction();

        try {
            $this->paymentModel->deleteByRoomId($roomId);
            $this->rentalModel->deleteByRoomId($roomId);
            $this->roomModel->delete($roomId);
            $db->commit();

            set_flash('success', 'Data Kamar Berhasil Dihapus');
        } catch (\Throwable $throwable) {
            $db->rollback();
            set_flash('error', 'Gagal menghapus kamar. Error Database.');
        }

        redirect_to('/admin/dashboard');
    }

    public function toggleStatus(): void
    {
        $this->requireAdmin();

        $roomId = (int) ($_GET['id'] ?? 0);
        $currentStatus = (string) ($_GET['status'] ?? 'Tersedia');
        $activeRental = $this->rentalModel->getActiveTenantByRoomId($roomId);

        if ($currentStatus === 'Tersedia') {
            set_flash('error', 'Untuk mengisi kamar, pilih penghuni dulu lewat menu edit kamar.');
            redirect_to('/admin/rooms/edit?id=' . $roomId);
        }

        if ($activeRental !== null) {
            $this->rentalModel->stopActiveByRentalId((int) $activeRental['id_sewa']);
        }

        $this->roomModel->toggleStatus($roomId, $currentStatus);
        set_flash('success', 'Kamar sekarang statusnya TERSEDIA (Kosong).');
        redirect_to('/admin/dashboard');
    }
}
