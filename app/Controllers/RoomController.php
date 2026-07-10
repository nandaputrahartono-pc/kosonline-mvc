<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Controller;
use App\Models\RoomModel;
use App\Models\KostModel;
use App\Models\PaymentModel;
use App\Models\PromoCodeModel;
use App\Models\RentalModel;
use App\Models\RoomReviewModel;
use App\Models\RoomGalleryModel;
use App\Models\UserModel;
use App\Models\WishlistModel;

final class RoomController extends Controller
{
    private RoomModel $roomModel;
    private KostModel $kostModel;
    private RoomGalleryModel $galleryModel;
    private UserModel $userModel;
    private RentalModel $rentalModel;
    private PaymentModel $paymentModel;
    private PromoCodeModel $promoCodeModel;
    private RoomReviewModel $reviewModel;
    private WishlistModel $wishlistModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
        $this->kostModel = new KostModel();
        $this->galleryModel = new RoomGalleryModel();
        $this->userModel = new UserModel();
        $this->rentalModel = new RentalModel();
        $this->paymentModel = new PaymentModel();
        $this->promoCodeModel = new PromoCodeModel();
        $this->reviewModel = new RoomReviewModel();
        $this->wishlistModel = new WishlistModel();
    }

    public function index(): void
    {
        $keyword = trim((string) ($_GET['cari'] ?? ''));
        $idKost = isset($_GET['cabang']) && $_GET['cabang'] !== '' ? (int) $_GET['cabang'] : null;
        $promoOnly = (string) ($_GET['promo'] ?? '') === '1';
        $sort = (string) ($_GET['sort'] ?? 'recommended');
        $allowedSorts = ['recommended', 'termurah', 'termahal', 'promo'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'recommended';
        }

        $perPage = 9;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $totalFilteredRooms = $this->roomModel->countAvailableFiltered($keyword, $idKost, $promoOnly);
        $totalPages = max(1, (int) ceil($totalFilteredRooms / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $rooms = $this->roomModel->searchAvailableFilteredPaginated(
            $keyword,
            $idKost,
            $promoOnly,
            $sort,
            $perPage,
            ($page - 1) * $perPage
        );
        $branches = $this->kostModel->getAll();
        $roomIds = array_map(static fn(array $room): int => (int) $room['id_kamar'], $rooms);
        $reviewSummaries = $this->reviewModel->getSummariesForRooms($roomIds);
        $savedRoomIds = (($_SESSION['status'] ?? null) === 'login_user' && isset($_SESSION['id_user']))
            ? $this->wishlistModel->getSavedRoomIds((int) $_SESSION['id_user'])
            : [];
        $availableSummary = $this->roomModel->availableSummary();
        $roomsSummary = [
            'total_available' => $availableSummary['total_available'],
            'result_count' => $totalFilteredRooms,
            'promo_count' => $availableSummary['promo_count'],
            'lowest_price' => $availableSummary['lowest_price'],
        ];

        $this->render('room/index', [
            'keyword' => $keyword,
            'idKost' => $idKost,
            'promoOnly' => $promoOnly,
            'sort' => $sort,
            'rooms' => $rooms,
            'branches' => $branches,
            'roomsSummary' => $roomsSummary,
            'reviewSummaries' => $reviewSummaries,
            'savedRoomIds' => $savedRoomIds,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'total_items' => $totalFilteredRooms,
                'from' => $totalFilteredRooms === 0 ? 0 : (($page - 1) * $perPage) + 1,
                'to' => min($totalFilteredRooms, $page * $perPage),
            ],
        ]);
    }

    public function detail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $room = $this->roomModel->findByIdWithKost($id);
        if ($room === null) {
            set_flash('error', 'Kamar tidak ditemukan.');
            redirect_to('/rooms');
        }

        $userId = (($_SESSION['status'] ?? null) === 'login_user' && isset($_SESSION['id_user']))
            ? (int) $_SESSION['id_user']
            : null;

        $this->render('room/detail', [
            'room' => $room,
            'gallery' => $this->galleryModel->getByRoomId($id),
            'reviews' => $this->reviewModel->getByRoomId($id),
            'reviewSummary' => $this->reviewModel->summaryByRoomId($id),
            'isWishlisted' => $userId !== null && $this->wishlistModel->isSaved($userId, $id),
            'isLoggedInUser' => $userId !== null,
        ]);
    }

    public function review(): void
    {
        $this->requireUser();

        $roomId = (int) ($_POST['id_kamar'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['komentar'] ?? ''));

        if ($roomId <= 0 || $this->roomModel->findByIdWithKost($roomId) === null) {
            set_flash('error', 'Kamar tidak ditemukan.');
            redirect_to('/rooms');
        }

        if ($rating < 1 || $rating > 5 || $comment === '') {
            set_flash('error', 'Rating dan komentar wajib diisi.');
            redirect_to('/rooms/detail?id=' . $roomId);
        }

        $this->reviewModel->upsert((int) $_SESSION['id_user'], $roomId, $rating, $comment);
        set_flash('success', 'Ulasan kamu berhasil disimpan.');
        redirect_to('/rooms/detail?id=' . $roomId . '#ulasan');
    }

    public function payment(): void
    {
        $this->requireUser();

        $id = (int) ($_GET['id'] ?? 0);
        $room = $this->roomModel->findByIdWithKost($id);
        if ($room === null) {
            set_flash('error', 'Kamar tidak ditemukan.');
            redirect_to('/rooms');
        }

        $userId = (int) ($_SESSION['id_user'] ?? 0);
        $user = $this->userModel->findById($userId);
        if ($user === null) {
            set_flash('error', 'Sesi login tidak valid. Silakan login ulang.');
            redirect_to('/login');
        }

        if ($this->isPost()) {
            if (($room['status'] ?? null) !== 'Tersedia') {
                set_flash('error', 'Kamar ini sudah tidak tersedia.');
                redirect_to('/rooms');
            }

            $moveInDate = trim((string) ($_POST['tanggal_masuk'] ?? ''));
            $promoCode = strtoupper(trim((string) ($_POST['kode_promo'] ?? '')));
            $method = trim((string) ($_POST['metode_bayar'] ?? ''));
            $confirmed = (string) ($_POST['konfirmasi_booking'] ?? '') === '1';
            $allowedMethods = ['manual_bca', 'manual_bri', 'manual_mandiri', 'manual_cash'];

            if (
                $moveInDate === '' ||
                !in_array($method, $allowedMethods, true) ||
                !$confirmed
            ) {
                set_flash('error', 'Pilih tanggal mulai ngekos, metode pembayaran, lalu setujui konfirmasi booking.');
                redirect_to('/rooms/payment?id=' . $id);
            }

            $moveIn = \DateTimeImmutable::createFromFormat('Y-m-d', $moveInDate);
            $today = new \DateTimeImmutable('today');
            if ($moveIn === false || $moveIn < $today) {
                set_flash('error', 'Tanggal mulai ngekos tidak valid.');
                redirect_to('/rooms/payment?id=' . $id);
            }

            $dueDate = $moveIn->modify('+1 month');
            $periodEnd = $dueDate->modify('-1 day');
            $roomPrice = (float) ($room['harga'] ?? 0);
            $roomDiscountPercent = max(0, min(100, (int) ($room['diskon_persen'] ?? 0)));
            $roomDiscount = $roomDiscountPercent > 0 ? $roomPrice * ($roomDiscountPercent / 100) : 0.0;
            $subtotal = max(0, $roomPrice - $roomDiscount);
            $promoDiscount = 0.0;
            $appliedPromoCode = null;

            if ($promoCode !== '') {
                $promo = $this->promoCodeModel->findValid($promoCode, $subtotal);
                if ($promo === null) {
                    set_flash('error', 'Kode promo tidak valid, sudah habis, atau belum memenuhi minimal transaksi.');
                    redirect_to('/rooms/payment?id=' . $id);
                }

                $promoDiscount = $this->promoCodeModel->calculateDiscount($promo, $subtotal);
                $appliedPromoCode = (string) $promo['kode'];
            }

            $adminFee = 0.0;
            $deposit = 0.0;
            $total = max(0, $subtotal - $promoDiscount + $adminFee + $deposit);
            $bookingCode = 'BOOK-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $invoiceNo = 'INV-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                $rentalId = $this->rentalModel->createPending(
                    $userId,
                    $id,
                    $moveIn->format('Y-m-d'),
                    $dueDate->format('Y-m-d'),
                    $bookingCode
                );

                $paymentId = $this->paymentModel->createPendingInvoice([
                    'id_sewa' => $rentalId,
                    'invoice_no' => $invoiceNo,
                    'bulan_tagihan' => $moveIn->format('F Y'),
                    'periode_mulai' => $moveIn->format('Y-m-d'),
                    'periode_selesai' => $periodEnd->format('Y-m-d'),
                    'harga_kamar' => $roomPrice,
                    'diskon_kamar' => $roomDiscount,
                    'kode_promo' => $appliedPromoCode,
                    'diskon_promo' => $promoDiscount,
                    'biaya_admin' => $adminFee,
                    'deposit' => $deposit,
                    'total_bayar' => $total,
                    'metode_bayar' => $method,
                    'nama_penyewa' => $user['nama_lengkap'],
                    'email_penyewa' => $user['email'],
                    'no_hp_penyewa' => $user['no_hp'],
                    'catatan' => 'Invoice manual. Menunggu verifikasi admin.',
                ]);

                if ($appliedPromoCode !== null) {
                    $this->promoCodeModel->incrementUsage($appliedPromoCode);
                }

                $this->roomModel->setStatus($id, 'Terisi');
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollback();
                set_flash('error', 'Booking gagal dibuat. Silakan coba lagi.');
                redirect_to('/rooms/payment?id=' . $id);
            }

            set_flash('success', 'Booking berhasil dibuat. Silakan ikuti instruksi pembayaran manual.');
            redirect_to('/rooms/invoice?id=' . $paymentId);
        }

        $this->render('room/payment', [
            'room' => $room,
            'user' => $user,
            'availablePromos' => $this->promoCodeModel->getActivePublic(),
        ]);
    }

    public function invoice(): void
    {
        $this->requireUser();

        $paymentId = (int) ($_GET['id'] ?? 0);
        $invoice = $this->paymentModel->findInvoiceById($paymentId);
        if ($invoice === null || (int) ($invoice['id_user'] ?? 0) !== (int) $_SESSION['id_user']) {
            set_flash('error', 'Invoice tidak ditemukan.');
            redirect_to('/rooms');
        }

        $this->render('room/invoice', [
            'invoice' => $invoice,
        ]);
    }
}
