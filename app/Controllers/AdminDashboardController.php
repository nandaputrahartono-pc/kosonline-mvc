<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ChatModel;
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
    private ChatModel $chatModel;

    public function __construct()
    {
        $this->kostModel = new KostModel();
        $this->roomModel = new RoomModel();
        $this->userModel = new UserModel();
        $this->messageModel = new MessageModel();
        $this->rentalModel = new RentalModel();
        $this->paymentModel = new PaymentModel();
        $this->chatModel = new ChatModel();
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

        // Pastikan tiap sewa aktif punya invoice periode berjalan (idempotent & self-healing:
        // sewa lama yang belum punya invoice bulan ke-2 pun otomatis dibuatkan).
        foreach ($this->rentalModel->activeRentalIds() as $activeRentalId) {
            $this->paymentModel->ensureOpenInvoice($activeRentalId);
        }

        $billings = $this->rentalModel->getAdminBillingRows();
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
        $roomsPerPage = 10;
        $roomsPage = max(1, (int) ($_GET['rooms_page'] ?? 1));
        $roomsTotalPages = max(1, (int) ceil($totalRooms / $roomsPerPage));
        if ($roomsPage > $roomsTotalPages) {
            $roomsPage = $roomsTotalPages;
        }

        $occupancyRate = $totalRooms > 0 ? (int) round(($occupiedRooms / $totalRooms) * 100) : 0;
        $paymentRate = $billingCount > 0 ? (int) round(($paidBillings / $billingCount) * 100) : 0;
        $revenueTrend = $this->paymentModel->monthlyRevenueTrend(6);
        $chatThreads = $this->chatModel->getAllThreads();
        $currentChatThreadId = (int) ($_GET['thread'] ?? 0);
        if ($currentChatThreadId <= 0 && $chatThreads !== []) {
            $currentChatThreadId = (int) $chatThreads[0]['id_thread'];
        }
        $currentChatThread = $currentChatThreadId > 0 ? $this->chatModel->getThreadForAdmin($currentChatThreadId) : null;

        // Tandai sudah dibaca begitu admin benar-benar membuka tab terkait,
        // supaya notifikasi berkurang secara wajar.
        $activeTab = (string) ($_GET['tab'] ?? 'dashboard');
        if ($activeTab === 'chat-user' && $currentChatThreadId > 0) {
            $this->chatModel->markThreadReadForAdmin($currentChatThreadId);
        }
        if ($activeTab === 'pesan-masuk') {
            $this->messageModel->markAllRead();
        }

        // Dihitung SETELAH mark-read supaya angka lonceng langsung akurat.
        $notifications = [
            'pembayaran' => $this->paymentModel->countPendingVerification(),
            'menunggak' => $this->rentalModel->countOverdue(),
            'chat' => $this->chatModel->countUnreadForAdmin(),
            'pesan' => $this->messageModel->countUnread(),
        ];
        $notifications['total'] = array_sum($notifications);

        $this->render('admin/dashboard', [
            'billingMonth' => $billingMonth,
            'adminName' => $_SESSION['admin_name'] ?? 'Admin',
            'notifications' => $notifications,
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
            'rooms' => $this->roomModel->getAllForAdminPaginated($roomsPerPage, ($roomsPage - 1) * $roomsPerPage),
            'roomPagination' => [
                'current_page' => $roomsPage,
                'per_page' => $roomsPerPage,
                'total_pages' => $roomsTotalPages,
                'total_items' => $totalRooms,
                'from' => $totalRooms === 0 ? 0 : (($roomsPage - 1) * $roomsPerPage) + 1,
                'to' => min($totalRooms, $roomsPage * $roomsPerPage),
            ],
            'users' => $this->userModel->getAll(),
            'billings' => $billings,
            'cancelledBookings' => $this->rentalModel->getCancelledBookings(),
            'messages' => $this->messageModel->getAll(),
            'locations' => $this->kostModel->getAll(),
            'chatThreads' => $chatThreads,
            'currentChatThread' => $currentChatThread,
            'chatMessages' => $currentChatThread !== null ? $this->chatModel->getMessages((int) $currentChatThread['id_thread']) : [],
            'activeTab' => (string) ($_GET['tab'] ?? 'dashboard'),
        ]);
    }
}
