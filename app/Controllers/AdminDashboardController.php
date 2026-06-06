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
        $billings = $this->rentalModel->getAdminBillingRows($billingMonth);
        $paidBillings = 0;
        $unpaidBillings = [];

        foreach ($billings as $billing) {
            if (($billing['status_verifikasi'] ?? null) === 'Lunas') {
                $paidBillings++;
                continue;
            }

            $unpaidBillings[] = $billing;
        }

        $totalRooms = (int) ($roomCounts['total'] ?? 0);
        $occupiedRooms = (int) ($roomCounts['terisi'] ?? 0);
        $billingCount = count($billings);
        $occupancyRate = $totalRooms > 0 ? (int) round(($occupiedRooms / $totalRooms) * 100) : 0;
        $paymentRate = $billingCount > 0 ? (int) round(($paidBillings / $billingCount) * 100) : 0;
        $revenueTrend = $this->paymentModel->monthlyRevenueTrend(6);

        $this->render('admin/dashboard', [
            'billingMonth' => $billingMonth,
            'adminName' => $_SESSION['admin_name'] ?? 'Admin',
            'successMessage' => flash('success'),
            'errorMessage' => flash('error'),
            'stats' => [
                'total_kost' => count($this->kostModel->getAll()),
                'total_kamar' => $totalRooms,
                'terisi' => $occupiedRooms,
                'kosong' => (int) ($roomCounts['kosong'] ?? 0),
                'penghuni_aktif' => $this->rentalModel->countActive(),
                'belum_bayar' => $this->paymentModel->unpaidCount($billingMonth),
                'pendapatan_total' => $this->paymentModel->totalPaidRevenue(),
                'pendapatan_bulan_ini' => $this->paymentModel->paidRevenueForBillingMonth($billingMonth),
                'rasio_okupansi' => $occupancyRate,
                'rasio_pembayaran' => $paymentRate,
            ],
            'revenueTrend' => $revenueTrend,
            'priorityBillings' => array_slice($unpaidBillings, 0, 5),
            'kosts' => $this->kostModel->getAll(),
            'rooms' => $this->roomModel->getAllForAdmin(),
            'users' => $this->userModel->getAll(),
            'billings' => $billings,
            'messages' => $this->messageModel->getAll(),
            'locations' => $this->kostModel->getAll(),
        ]);
    }
}
