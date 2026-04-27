<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PaymentModel;

final class AdminPaymentController extends Controller
{
    private PaymentModel $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
    }

    public function update(): void
    {
        $this->requireAdmin();

        $rentalId = (int) ($_GET['id'] ?? 0);
        $billingMonth = urldecode((string) ($_GET['bulan'] ?? ''));
        $action = (string) ($_GET['aksi'] ?? '');
        $amount = (float) ($_GET['nominal'] ?? 0);

        if ($action === 'lunas') {
            $this->paymentModel->markPaid($rentalId, $billingMonth, $amount);
            set_flash('success', 'Status berhasil diubah menjadi SUDAH BAYAR.');
        }

        if ($action === 'batal') {
            $this->paymentModel->cancelPayment($rentalId, $billingMonth);
            set_flash('success', 'Status berhasil diubah menjadi BELUM BAYAR.');
        }

        redirect_to('/admin/dashboard');
    }
}
