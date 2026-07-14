<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\FileUploadService;
use App\Models\KostModel;
use App\Models\PaymentModel;
use App\Models\RentalModel;
use App\Models\RoomGalleryModel;
use App\Models\RoomModel;
use App\Models\UserModel;
use RuntimeException;

final class AdminRoomController extends Controller
{
    private RoomModel $roomModel;
    private KostModel $kostModel;
    private UserModel $userModel;
    private RentalModel $rentalModel;
    private PaymentModel $paymentModel;
    private RoomGalleryModel $galleryModel;
    private FileUploadService $uploader;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
        $this->kostModel = new KostModel();
        $this->userModel = new UserModel();
        $this->rentalModel = new RentalModel();
        $this->paymentModel = new PaymentModel();
        $this->galleryModel = new RoomGalleryModel();
        $this->uploader = new FileUploadService();
    }

    public function create(): void
    {
        $this->requireAdmin();

        if ($this->isPost() && isset($_POST['simpan'])) {
            $db = Database::getInstance();
            $uploadedFiles = [];
            $tenantId = (int) ($_POST['id_user'] ?? 0);
            $kostId = (int) ($_POST['id_kost'] ?? 0);
            $roomNumber = trim((string) ($_POST['nomor_kamar'] ?? ''));
            $floor = (int) ($_POST['lantai'] ?? 0);
            $price = (float) ($_POST['harga'] ?? 0);
            $discount = (int) ($_POST['diskon_persen'] ?? 0);

            if ($this->kostModel->findById($kostId) === null || $roomNumber === '' || $floor < 1 || $price <= 0 || $discount < 0 || $discount > 100) {
                set_flash('error', 'Cabang, nomor kamar, lantai, harga, dan diskon harus valid.');
                redirect_to('/admin/rooms/create');
            }

            if ($tenantId > 0 && $this->rentalModel->getActiveByUserIdOnly($tenantId) !== null) {
                set_flash('error', 'User yang dipilih masih terdaftar sebagai penghuni aktif di kamar lain.');
                redirect_to('/admin/rooms/create');
            }

            $db->beginTransaction();

            try {
                $roomId = $this->roomModel->create([
                    'id_kost' => $kostId,
                    'nomor_kamar' => $roomNumber,
                    'lantai' => $floor,
                    'fasilitas' => trim((string) ($_POST['fasilitas'] ?? '')),
                    'deskripsi_kamar' => trim((string) ($_POST['deskripsi_kamar'] ?? '')),
                    'harga' => $price,
                    'status' => $tenantId > 0 ? 'Terisi' : 'Tersedia',
                    'diskon_persen' => $discount,
                ]);

                if ($tenantId > 0) {
                    $this->rentalModel->createActive($tenantId, $roomId);
                }

                $this->uploadNewGallery($roomId, $uploadedFiles);

                $db->commit();
                set_flash('success', 'Data Kamar Berhasil Ditambahkan');
                redirect_to('/admin/rooms/edit?id=' . $roomId);
            } catch (\Throwable $throwable) {
                $db->rollback();
                $this->deleteUploadedFiles($uploadedFiles);
                $message = $throwable instanceof RuntimeException && !$throwable instanceof \PDOException
                    ? $throwable->getMessage()
                    : 'Gagal menambahkan kamar.';
                set_flash('error', $message);
                redirect_to('/admin/rooms/create');
            }
        }

        $this->render('admin/forms/room-create', [
            'kosts' => $this->kostModel->getAll(),
            'users' => $this->userModel->getAvailableForRental(),
            'gallery' => [],
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
            $uploadedFiles = [];
            $removedFiles = [];
            $newTenant = (int) ($_POST['id_user'] ?? 0);
            $kostId = (int) ($_POST['id_kost'] ?? 0);
            $roomNumber = trim((string) ($_POST['nomor_kamar'] ?? ''));
            $floor = (int) ($_POST['lantai'] ?? 0);
            $price = (float) ($_POST['harga'] ?? 0);
            $discount = (int) ($_POST['diskon_persen'] ?? 0);

            if ($this->kostModel->findById($kostId) === null || $roomNumber === '' || $floor < 1 || $price <= 0 || $discount < 0 || $discount > 100) {
                set_flash('error', 'Cabang, nomor kamar, lantai, harga, dan diskon harus valid.');
                redirect_to('/admin/rooms/edit?id=' . $id);
            }

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
                    'id_kost' => $kostId,
                    'nomor_kamar' => $roomNumber,
                    'lantai' => $floor,
                    'fasilitas' => trim((string) ($_POST['fasilitas'] ?? '')),
                    'deskripsi_kamar' => trim((string) ($_POST['deskripsi_kamar'] ?? '')),
                    'harga' => $price,
                    'status' => $newTenant > 0 ? 'Terisi' : 'Tersedia',
                    'diskon_persen' => $discount,
                ]);

                if ($newTenant !== $currentTenant) {
                    if ($activeRental !== null) {
                        $this->rentalModel->stopActiveByRentalId((int) $activeRental['id_sewa']);
                    }

                    if ($newTenant > 0) {
                        $this->rentalModel->createActive($newTenant, $id);
                    }
                }

                $this->updateExistingGallery($id, $removedFiles);
                $this->uploadNewGallery($id, $uploadedFiles);

                $db->commit();
                $this->deleteUploadedFiles($removedFiles);
                set_flash('success', 'Data Kamar & Penghuni Berhasil Diupdate');
                redirect_to('/admin/rooms/edit?id=' . $id);
            } catch (\Throwable $throwable) {
                $db->rollback();
                $this->deleteUploadedFiles($uploadedFiles);
                $message = $throwable instanceof RuntimeException && !$throwable instanceof \PDOException
                    ? $throwable->getMessage()
                    : 'Gagal mengupdate kamar.';
                set_flash('error', $message);
                redirect_to('/admin/rooms/edit?id=' . $id);
            }
        }

        $this->render('admin/forms/room-edit', [
            'room' => $room,
            'kosts' => $this->kostModel->getAll(),
            'users' => $this->userModel->getAvailableForRental($currentTenant),
            'currentTenant' => $currentTenant,
            'gallery' => $this->galleryModel->getByRoomId($id),
        ]);
    }

    /**
     * Menghapus kamar TIDAK BOLEH menghapus pembukuan.
     *
     * Dulu method ini membuang semua pembayaran kamar (termasuk yang Lunas) beserta sewanya,
     * sehingga Total Pendapatan bisa lenyap tanpa peringatan dan penghuni yang sedang tinggal
     * ikut hilang. Sekarang bertingkat:
     *   1. masih ada penghuni/booking berjalan -> tolak;
     *   2. ada jejak pembayaran               -> ARSIPKAN (data keuangan utuh);
     *   3. belum pernah disewa sama sekali    -> baru boleh dihapus betulan.
     */
    public function delete(): void
    {
        $this->requireAdmin();

        $roomId = (int) ($_POST['id'] ?? 0);

        if ($this->roomModel->findById($roomId) === null) {
            set_flash('error', 'Data kamar tidak ditemukan.');
            redirect_to('/admin/dashboard?tab=data-kamar');
        }

        if ($this->roomModel->hasRunningRental($roomId)) {
            set_flash('error', 'Kamar ini masih punya penghuni atau booking yang berjalan. Hentikan atau batalkan sewanya dulu.');
            redirect_to('/admin/dashboard?tab=data-kamar');
        }

        if ($this->roomModel->hasBillingHistory($roomId)) {
            if ($this->roomModel->archive($roomId)) {
                set_flash('success', 'Kamar diarsipkan. Kamar hilang dari halaman penyewa, tapi riwayat pembayaran & Total Pendapatan tetap utuh.');
            } else {
                set_flash('error', 'Gagal mengarsipkan kamar.');
            }

            redirect_to('/admin/dashboard?tab=data-kamar');
        }

        // Kamar polos: tak pernah disewa, tak ada apa pun untuk diselamatkan.
        $db = Database::getInstance();
        $galleryFiles = array_column($this->galleryModel->getByRoomId($roomId), 'nama_file');

        $db->beginTransaction();

        try {
            $this->rentalModel->deleteByRoomId($roomId);
            $this->roomModel->delete($roomId);
            $db->commit();
            $this->deleteUploadedFiles($galleryFiles);

            set_flash('success', 'Data Kamar Berhasil Dihapus');
        } catch (\Throwable $throwable) {
            $db->rollback();
            set_flash('error', 'Gagal menghapus kamar. Error Database.');
        }

        redirect_to('/admin/dashboard?tab=data-kamar');
    }

    private function updateExistingGallery(int $roomId, array &$removedFiles): void
    {
        $entries = $_POST['existing_gallery'] ?? [];
        if (!is_array($entries)) {
            return;
        }

        foreach ($entries as $galleryId => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $galleryId = (int) $galleryId;
            $gallery = $this->galleryModel->findByIdForRoom($galleryId, $roomId);
            if ($gallery === null) {
                continue;
            }

            if (!empty($entry['hapus'])) {
                $this->galleryModel->delete($galleryId, $roomId);
                $removedFiles[] = (string) $gallery['nama_file'];
                continue;
            }

            $this->galleryModel->update($galleryId, $roomId, [
                'kategori' => $this->cleanCategory($entry['kategori'] ?? ''),
                'judul' => $this->cleanTitle($entry['judul'] ?? ''),
                'urutan' => $this->cleanOrder($entry['urutan'] ?? 0),
            ]);
        }
    }

    private function uploadNewGallery(int $roomId, array &$uploadedFiles): void
    {
        $files = $this->normalizeGalleryFiles($_FILES['gallery_photos'] ?? null);
        if (count($files) > 20) {
            throw new RuntimeException('Maksimal 20 foto galeri dalam satu kali simpan.');
        }

        // Satu kategori berlaku untuk semua foto dalam satu batch upload.
        $batchCategory = $this->cleanCategory($_POST['gallery_batch_category'] ?? '');
        $order = 0;

        foreach ($files as $file) {
            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $filename = $this->uploader->upload($file, false);
            if ($filename === null) {
                continue;
            }

            $uploadedFiles[] = $filename;
            $this->galleryModel->create($roomId, [
                'kategori' => $batchCategory,
                'judul' => null,
                'nama_file' => $filename,
                'urutan' => $order,
            ]);
            $order++;
        }
    }

    private function normalizeGalleryFiles(?array $files): array
    {
        if ($files === null || !isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            $normalized[$index] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }

    private function cleanCategory(mixed $category): string
    {
        $category = trim((string) $category);

        return mb_substr($category !== '' ? $category : 'Lainnya', 0, 60);
    }

    private function cleanTitle(mixed $title): ?string
    {
        $title = trim((string) $title);

        return $title === '' ? null : mb_substr($title, 0, 120);
    }

    private function cleanOrder(mixed $order): int
    {
        return max(0, min(65535, (int) $order));
    }

    private function deleteUploadedFiles(array $filenames): void
    {
        foreach ($filenames as $filename) {
            $this->uploader->delete((string) $filename);
        }
    }

    public function toggleStatus(): void
    {
        $this->requireAdmin();

        $roomId = (int) ($_POST['id'] ?? 0);
        $room = $this->roomModel->findById($roomId);

        if ($room === null) {
            set_flash('error', 'Data kamar tidak ditemukan.');
            redirect_to('/admin/dashboard');
        }

        $currentStatus = (string) $room['status'];
        $activeRental = $this->rentalModel->getActiveTenantByRoomId($roomId);

        // Kamar terarsip: pulihkan saja, jangan diperlakukan seperti kamar terisi
        // (tak ada penghuni yang perlu dihentikan).
        if ($currentStatus === 'Arsip') {
            if ($this->roomModel->setStatus($roomId, 'Tersedia')) {
                set_flash('success', 'Kamar dipulihkan dari arsip dan kembali tersedia untuk disewa.');
            } else {
                set_flash('error', 'Kamar gagal dipulihkan.');
            }

            redirect_to('/admin/dashboard?tab=data-kamar');
        }

        if ($currentStatus === 'Tersedia') {
            set_flash('error', 'Untuk mengisi kamar, pilih penghuni dulu lewat menu edit kamar.');
            redirect_to('/admin/rooms/edit?id=' . $roomId);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            if ($activeRental !== null) {
                $this->rentalModel->stopActiveByRentalId((int) $activeRental['id_sewa']);
            }

            $this->roomModel->toggleStatus($roomId, $currentStatus);
            $db->commit();
            set_flash('success', 'Kamar sekarang statusnya TERSEDIA (Kosong).');
        } catch (\Throwable $throwable) {
            $db->rollback();
            set_flash('error', 'Status kamar gagal diperbarui.');
        }

        redirect_to('/admin/dashboard');
    }
}
