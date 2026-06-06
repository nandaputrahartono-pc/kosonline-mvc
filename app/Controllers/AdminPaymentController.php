<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PaymentModel;
use App\Models\RentalModel;
use App\Models\RoomModel;

final class AdminPaymentController extends Controller
{
    private PaymentModel $paymentModel;
    private RentalModel $rentalModel;
    private RoomModel $roomModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->rentalModel = new RentalModel();
        $this->roomModel = new RoomModel();
    }

    public function update(): void
    {
        $this->requireAdmin();

        $rentalId = (int) ($_POST['id'] ?? 0);
        $billingMonth = date('F Y');
        $action = (string) ($_POST['aksi'] ?? '');
        $rental = $this->rentalModel->findByIdWithRoom($rentalId);

        if ($rental === null) {
            set_flash('error', 'Data sewa tidak ditemukan.');
            redirect_to('/admin/dashboard');
        }

        if ($action === 'lunas') {
            $latestPayment = $this->paymentModel->getHistoryByRentalId($rentalId)[0] ?? null;
            $amount = (float) ($latestPayment['total_bayar'] ?? $latestPayment['nominal'] ?? $rental['harga']);

            if (($rental['status_sewa'] ?? '') === 'Menunggu Pembayaran') {
                $this->paymentModel->markPaidByRental($rentalId, $amount);
                $this->rentalModel->activate($rentalId);
                $this->roomModel->setStatus((int) $rental['id_kamar'], 'Terisi');
            } else {
                $this->paymentModel->markPaid($rentalId, $billingMonth, $amount);
            }

            set_flash('success', 'Status berhasil diubah menjadi SUDAH BAYAR.');
        } elseif ($action === 'batal') {
            if (($rental['status_sewa'] ?? '') === 'Menunggu Pembayaran') {
                $this->paymentModel->rejectLatestByRental($rentalId);
                $this->rentalModel->cancelPending($rentalId);
                $this->roomModel->setStatus((int) $rental['id_kamar'], 'Tersedia');
                set_flash('success', 'Booking pending berhasil dibatalkan.');
            } else {
                $this->paymentModel->cancelPayment($rentalId, $billingMonth);
                set_flash('success', 'Status berhasil diubah menjadi BELUM BAYAR.');
            }
        } else {
            set_flash('error', 'Aksi pembayaran tidak valid.');
        }

        redirect_to('/admin/dashboard');
    }
}
