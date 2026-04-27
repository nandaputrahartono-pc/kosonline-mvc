<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\KostModel;
use App\Models\MessageModel;
use App\Models\PaymentModel;
use App\Models\RentalModel;
use App\Models\RoomModel;
use App\Models\UserModel;

final class AdminDashboardController extends Controller
{
    private KostModel $kostModel;
    private RoomModel $roomModel;
    private UserModel $userModel;
    private MessageModel $messageModel;
    private RentalModel $rentalModel;
    private PaymentModel $paymentModel;

    public function __construct()
    {
        $this->kostModel = new KostModel();
        $this->roomModel = new RoomModel();
        $this->userModel = new UserModel();
        $this->messageModel = new MessageModel();
        $this->rentalModel = new RentalModel();
        $this->paymentModel = new PaymentModel();
    }

    public function index(): void
    {
        if (($_SESSION['status'] ?? null) === 'login_admin') {
            redirect_to('/admin/dashboard');
        }

        redirect_to('/admin/login');
    }

    public function dashboard(): void
    {
        $this->requireAdmin();

        $billingMonth = date('F Y');
        $roomCounts = $this->roomModel->getCounts();

        $this->render('admin/dashboard', [
            'billingMonth' => $billingMonth,
            'successMessage' => flash('success'),
            'errorMessage' => flash('error'),
            'stats' => [
                'total_kost' => count($this->kostModel->getAll()),
                'total_kamar' => (int) ($roomCounts['total'] ?? 0),
                'terisi' => (int) ($roomCounts['terisi'] ?? 0),
                'kosong' => (int) ($roomCounts['kosong'] ?? 0),
                'penghuni_aktif' => $this->rentalModel->countActive(),
                'belum_bayar' => $this->paymentModel->unpaidCount($billingMonth),
            ],
            'kosts' => $this->kostModel->getAll(),
            'rooms' => $this->roomModel->getAllForAdmin(),
            'users' => $this->userModel->getAll(),
            'billings' => $this->rentalModel->getAdminBillingRows($billingMonth),
            'messages' => $this->messageModel->getAll(),
            'locations' => $this->kostModel->getAll(),
        ]);
    }
}
