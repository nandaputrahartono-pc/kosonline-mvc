<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PaymentModel;
use App\Models\RentalModel;
use App\Models\UserModel;

final class MemberController extends Controller
{
    private UserModel $userModel;
    private RentalModel $rentalModel;
    private PaymentModel $paymentModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->rentalModel = new RentalModel();
        $this->paymentModel = new PaymentModel();
    }

    public function dashboard(): void
    {
        $this->requireUser();

        $userId = (int) $_SESSION['id_user'];

        if ($this->isPost() && isset($_POST['update_profil'])) {
            $email = trim((string) $_POST['email']);
            if ($this->userModel->emailExists($email, $userId)) {
                set_flash('error', 'Email sudah dipakai user lain.');
                redirect_to('/member/dashboard');
            }

            $payload = [
                'nama_lengkap' => trim((string) $_POST['nama']),
                'email' => $email,
                'no_hp' => trim((string) $_POST['no_hp']),
            ];

            $password = trim((string) ($_POST['password'] ?? ''));
            if ($password !== '') {
                $payload['password'] = $password;
            }

            $this->userModel->update($userId, $payload);
            $_SESSION['nama'] = $payload['nama_lengkap'];
            set_flash('success', 'Profil berhasil diperbarui!');
            redirect_to('/member/dashboard');
        }

        $user = $this->userModel->findById($userId);
        $rental = $this->rentalModel->getActiveByUserId($userId);
        $paymentHistory = [];

        $summary = [
            'nama_kost' => '-',
            'kamar_info' => 'Belum Sewa',
            'harga' => 0,
            'status_bayar' => 'Tidak Ada Tagihan',
            'class_badge' => 'success',
            'jatuh_tempo' => '-',
        ];

        if ($rental !== null) {
            $paymentHistory = $this->paymentModel->getHistoryByRentalId((int) $rental['id_sewa']);
            $summary['nama_kost'] = $rental['nama_kost'];
            $summary['kamar_info'] = 'Kamar ' . $rental['nomor_kamar'] . ' (Lt. ' . $rental['lantai'] . ')';
            $summary['harga'] = (float) $rental['harga'];

            $dueDate = '-';
            if (!empty($rental['tanggal_masuk'])) {
                $dueDate = date('Y-m-d', strtotime($rental['tanggal_masuk'] . ' +1 month'));
            }

            $summary['jatuh_tempo'] = $dueDate;

            $latestPayment = $paymentHistory[0] ?? null;
            if ($latestPayment !== null && $latestPayment['status_verifikasi'] === 'Lunas') {
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
            'paymentHistory' => $paymentHistory,
            'summary' => $summary,
            'successMessage' => flash('success'),
            'errorMessage' => flash('error'),
        ]);
    }
}
