<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ChatModel;
use App\Models\PaymentModel;
use App\Models\RentalModel;
use App\Models\UserModel;
use App\Models\WishlistModel;

final class MemberController extends Controller
{
    private UserModel $userModel;
    private RentalModel $rentalModel;
    private PaymentModel $paymentModel;
    private WishlistModel $wishlistModel;
    private ChatModel $chatModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->rentalModel = new RentalModel();
        $this->paymentModel = new PaymentModel();
        $this->wishlistModel = new WishlistModel();
        $this->chatModel = new ChatModel();
    }

    public function dashboard(): void
    {
        $this->requireUser();

        $userId = (int) $_SESSION['id_user'];

        if ($this->isPost() && isset($_POST['update_profil'])) {
            $name = trim((string) ($_POST['nama'] ?? ''));
            $username = strtolower(trim((string) ($_POST['username'] ?? '')));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = trim((string) ($_POST['no_hp'] ?? ''));

            if ($name === '' || $username === '' || $phone === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                set_flash('error', 'Nama, username, email valid, dan nomor handphone wajib diisi.');
                redirect_to('/member/dashboard');
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

            $this->userModel->update($userId, $payload);
            $_SESSION['nama'] = $payload['nama_lengkap'];
            set_flash('success', 'Profil berhasil diperbarui!');
            redirect_to('/member/dashboard');
        }

        $user = $this->userModel->findById($userId);
        if ($user === null) {
            set_flash('error', 'Sesi login tidak valid. Silakan login ulang.');
            redirect_to('/login');
        }

        $_SESSION['nama'] = $user['nama_lengkap'];
        $_SESSION['foto_profil'] = $user['foto_profil'] ?? 'default.jpg';

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
        $wishlistRooms = $this->wishlistModel->getByUserId($userId);
        $chatThreads = $this->chatModel->getThreadsByUserId($userId);
        $currentThreadId = (int) ($_GET['thread'] ?? 0);
        if ($currentThreadId <= 0 && $chatThreads !== []) {
            $currentThreadId = (int) $chatThreads[0]['id_thread'];
        }
        $currentThread = $currentThreadId > 0 ? $this->chatModel->getThreadForUser($currentThreadId, $userId) : null;
        $chatMessages = $currentThread !== null ? $this->chatModel->getMessages((int) $currentThread['id_thread']) : [];

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
            'wishlistRooms' => $wishlistRooms,
            'chatThreads' => $chatThreads,
            'currentThread' => $currentThread,
            'chatMessages' => $chatMessages,
            'activeTab' => (string) ($_GET['tab'] ?? 'dashboard'),
            'summary' => $summary,
            'successMessage' => flash('success'),
            'errorMessage' => flash('error'),
        ]);
    }
}
