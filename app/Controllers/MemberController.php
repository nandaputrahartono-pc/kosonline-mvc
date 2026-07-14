<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\FileUploadService;
use App\Models\ChatModel;
use App\Models\PaymentModel;
use App\Models\RentalModel;
use App\Models\UserModel;

final class MemberController extends Controller
{
    private UserModel $userModel;
    private RentalModel $rentalModel;
    private PaymentModel $paymentModel;
    private ChatModel $chatModel;
    private FileUploadService $uploader;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->rentalModel = new RentalModel();
        $this->paymentModel = new PaymentModel();
        $this->chatModel = new ChatModel();
        $this->uploader = new FileUploadService();
    }

    public function dashboard(): void
    {
        $this->requireUser();

        if ((string) ($_GET['tab'] ?? '') === 'wishlist') {
            redirect_to('/wishlist');
        }

        $userId = (int) $_SESSION['id_user'];

        if ($this->isPost() && isset($_POST['update_profil'])) {
            $name = trim((string) ($_POST['nama'] ?? ''));
            $username = strtolower(trim((string) ($_POST['username'] ?? '')));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = trim((string) ($_POST['no_hp'] ?? ''));

            if ($name === '' || $username === '' || $phone === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                set_flash('error', 'Nama, username, email valid, dan nomor handphone wajib diisi.');
                redirect_to('/member/dashboard?tab=profil');
            }

            if (!preg_match('/^[0-9]{8,15}$/', $phone)) {
                set_flash('error', 'Nomor handphone hanya boleh angka (8–15 digit).');
                redirect_to('/member/dashboard?tab=profil');
            }

            if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
                set_flash('error', 'Username hanya boleh huruf kecil, angka, underscore, minimal 3 karakter.');
                redirect_to('/member/dashboard');
            }

            if ($this->userModel->usernameExists($username, $userId)) {
                set_flash('error', 'Username sudah dipakai user lain.');
                redirect_to('/member/dashboard');
            }

            if ($this->userModel->emailExists($email, $userId)) {
                set_flash('error', 'Email sudah dipakai user lain.');
                redirect_to('/member/dashboard');
            }

            $payload = [
                'nama_lengkap' => $name,
                'username' => $username,
                'email' => $email,
                'no_hp' => $phone,
            ];

            $password = trim((string) ($_POST['password'] ?? ''));
            if ($password !== '') {
                if (strlen($password) < 6) {
                    set_flash('error', 'Password minimal 6 karakter.');
                    redirect_to('/member/dashboard');
                }
                $payload['password'] = $password;
            }

            // Foto profil opsional: hanya diproses kalau user memilih file baru.
            $newPhoto = null;
            try {
                $newPhoto = $this->uploader->upload($_FILES['foto_profil'] ?? null, false);
            } catch (\RuntimeException $exception) {
                set_flash('error', $exception->getMessage());
                redirect_to('/member/dashboard?tab=profil');
            }

            if ($newPhoto !== null) {
                $existing = $this->userModel->findById($userId);
                $previousPhoto = (string) ($existing['foto_profil'] ?? '');
                $this->userModel->updatePhoto($userId, $newPhoto);
                if ($previousPhoto !== '' && $previousPhoto !== 'default.jpg') {
                    $this->uploader->delete($previousPhoto);
                }
                $_SESSION['foto_profil'] = $newPhoto;
            }

            $this->userModel->update($userId, $payload);
            $_SESSION['nama'] = $payload['nama_lengkap'];
            set_flash('success', 'Profil berhasil diperbarui!');
            redirect_to('/member/dashboard?tab=profil');
        }

        $user = $this->userModel->findById($userId);
        if ($user === null) {
            set_flash('error', 'Sesi login tidak valid. Silakan login ulang.');
            redirect_to('/login');
        }

        $_SESSION['nama'] = $user['nama_lengkap'];
        $_SESSION['foto_profil'] = $user['foto_profil'] ?? 'default.jpg';

        // Pastikan sewa aktif punya invoice periode berjalan supaya user bisa membayar
        // bulan berikutnya lewat aplikasi (idempotent; juga menyembuhkan sewa lama).
        $activeRental = $this->rentalModel->getActiveByUserIdOnly($userId);
        if ($activeRental !== null) {
            $this->paymentModel->ensureOpenInvoice((int) $activeRental['id_sewa']);
        }

        $rentals = $this->rentalModel->getDashboardRowsByUserId($userId);
        $rental = null;
        foreach ($rentals as $row) {
            if (in_array($row['status_sewa'], ['Menunggu Pembayaran', 'Aktif'], true)) {
                $rental = $row;
                break;
            }
        }

        $paymentHistory = $this->paymentModel->getHistoryByUserId($userId);
        $latestInvoice = $paymentHistory[0] ?? null;
        $this->chatModel->getOrCreateThread($userId);
        $chatThreads = $this->chatModel->getThreadsByUserId($userId);
        $currentThreadId = (int) ($_GET['thread'] ?? 0);
        if ($currentThreadId <= 0 && $chatThreads !== []) {
            $currentThreadId = (int) $chatThreads[0]['id_thread'];
        }
        $currentThread = $currentThreadId > 0 ? $this->chatModel->getThreadForUser($currentThreadId, $userId) : null;

        // Begitu user benar-benar membuka tab chat, pesan admin ditandai sudah dibaca
        // supaya badge angka di ikon chat hilang.
        if ((string) ($_GET['tab'] ?? '') === 'chat' && $currentThread !== null) {
            $this->chatModel->markThreadReadForUser((int) $currentThread['id_thread'], $userId);
        }

        $chatMessages = $currentThread !== null ? $this->chatModel->getMessages((int) $currentThread['id_thread']) : [];
        $pendingRoomId = (int) ($_GET['pending_room'] ?? 0);
        $pendingRoomCard = $pendingRoomId > 0 ? $this->chatModel->roomCardByRoomId($pendingRoomId) : null;
        if ($pendingRoomCard === null) {
            $pendingRoomId = 0;
        }

        $summary = [
            'nama_kost' => '-',
            'kamar_info' => 'Belum Sewa',
            'harga' => 0,
            'status_bayar' => 'Tidak Ada Tagihan',
            'class_badge' => 'success',
            'jatuh_tempo' => '-',
            'total_pesanan' => count($rentals),
            'total_invoice' => count($paymentHistory),
            'tagihan_terdekat' => 0,
        ];

        if ($rental !== null) {
            $summary['nama_kost'] = $rental['nama_kost'];
            $summary['kamar_info'] = 'Kamar ' . $rental['nomor_kamar'] . ' (Lt. ' . $rental['lantai'] . ')';
            $summary['harga'] = (float) ($rental['total_bayar'] ?? $rental['harga']);
            $summary['tagihan_terdekat'] = (float) ($rental['total_bayar'] ?? $rental['harga']);

            $dueDate = (string) ($rental['jatuh_tempo'] ?? '-');
            if ($dueDate === '' || $dueDate === '0000-00-00') {
                $dueDate = !empty($rental['tanggal_masuk'])
                    ? date('Y-m-d', strtotime($rental['tanggal_masuk'] . ' +1 month'))
                    : '-';
            }

            $summary['jatuh_tempo'] = $dueDate;

            if (($rental['status_sewa'] ?? '') === 'Menunggu Pembayaran') {
                $summary['status_bayar'] = 'Menunggu Verifikasi';
                $summary['class_badge'] = 'warning';
            } elseif ($latestInvoice !== null && $latestInvoice['status_verifikasi'] === 'Lunas') {
                $summary['status_bayar'] = 'Lunas';
                $summary['class_badge'] = 'success';
            } elseif ($dueDate !== '-' && date('Y-m-d') > $dueDate) {
                $summary['status_bayar'] = 'Terlambat';
                $summary['class_badge'] = 'danger';
            } else {
                $summary['status_bayar'] = 'Belum Bayar';
                $summary['class_badge'] = 'warning';
            }
        }

        $this->render('member/dashboard', [
            'user' => $user,
            'rental' => $rental,
            'rentals' => $rentals,
            'paymentHistory' => $paymentHistory,
            'latestInvoice' => $latestInvoice,
            'chatThreads' => $chatThreads,
            'currentThread' => $currentThread,
            'chatMessages' => $chatMessages,
            'pendingRoomId' => $pendingRoomId,
            'pendingRoomCard' => $pendingRoomCard,
            'activeTab' => (string) ($_GET['tab'] ?? 'dashboard'),
            'summary' => $summary,
            'successMessage' => flash('success'),
            'errorMessage' => flash('error'),
        ]);
    }

    public function deleteOrder(): void
    {
        $this->requireUser();

        $rentalId = (int) ($_POST['id_sewa'] ?? 0);
        $userId = (int) $_SESSION['id_user'];
        $rental = $this->rentalModel->findByIdForUser($rentalId, $userId);

        // Hanya sewa yang sudah BERAKHIR yang boleh dibuang dari daftar.
        $endedStatuses = ['Dibatalkan', 'Berhenti'];
        if ($rental === null || !in_array((string) ($rental['status_sewa'] ?? ''), $endedStatuses, true)) {
            set_flash('error', 'Hanya sewa yang sudah berakhir atau dibatalkan yang bisa dihapus dari daftar.');
            redirect_to('/member/dashboard?tab=pesananku');
        }

        // SEMBUNYIKAN, bukan hapus: catatan pembayaran sengaja dibiarkan utuh supaya
        // Total Pendapatan admin & riwayat keuangan tidak ikut hilang.
        if (!$this->rentalModel->hideForUser($rentalId, $userId)) {
            set_flash('error', 'Gagal menghapus pesanan dari daftar. Silakan coba lagi.');
            redirect_to('/member/dashboard?tab=pesananku');
        }

        set_flash('success', 'Pesanan dihapus dari daftar kamu.');
        redirect_to('/member/dashboard?tab=pesananku');
    }
}
