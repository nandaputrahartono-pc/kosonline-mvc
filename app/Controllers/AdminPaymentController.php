<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
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
        $action = (string) ($_POST['aksi'] ?? '');
        $rental = $this->rentalModel->findByIdWithRoom($rentalId);

        if ($rental === null) {
            set_flash('error', 'Data sewa tidak ditemukan.');
            redirect_to('/admin/dashboard');
        }

        $isPendingBooking = ($rental['status_sewa'] ?? '') === 'Menunggu Pembayaran';

        // Verifikasi berbasis INVOICE (bukan bulan kalender) -> tak ada lagi baris stub/ganda.
        $invoice = $this->paymentModel->latestInvoiceByRental($rentalId);
        $invoiceId = (int) ($invoice['id_pembayaran'] ?? 0);

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            if ($action === 'lunas') {
                $amount = (float) ($invoice['total_bayar'] ?? 0);
                if ($amount <= 0) {
                    $amount = (float) ($invoice['nominal'] ?? $rental['harga']);
                }

                if ($invoiceId > 0) {
                    $this->paymentModel->markInvoicePaid($invoiceId, $amount);
                }

                if ($isPendingBooking) {
                    // Booking awal: aktifkan sewa. Jatuh tempo awal (masuk + 1 bulan) SUDAH
                    // menunjuk siklus berikutnya, jadi TIDAK dimajukan.
                    $this->rentalModel->activate($rentalId);
                    $this->roomModel->setStatus((int) $rental['id_kamar'], 'Terisi');
                } else {
                    // Pelunasan bulanan: jatuh tempo maju satu bulan.
                    $this->rentalModel->advanceDueDate($rentalId);
                }

                // Lahirkan invoice periode BERIKUTNYA supaya user bisa bayar bulan depan.
                $this->paymentModel->ensureOpenInvoice($rentalId);

                set_flash('success', 'Pembayaran diverifikasi LUNAS. Invoice periode berikutnya sudah dibuat.');
            } elseif ($action === 'batal') {
                if ($isPendingBooking) {
                    $this->paymentModel->rejectLatestByRental($rentalId);
                    $this->rentalModel->cancelPending($rentalId);
                    $this->roomModel->setStatus((int) $rental['id_kamar'], 'Tersedia');
                    set_flash('success', 'Booking pending berhasil dibatalkan.');
                } else {
                    // Non-destruktif: invoice dikembalikan ke 'Menunggu' (tidak dihapus),
                    // jatuh tempo mundur, lalu invoice "masa depan" dibersihkan.
                    if ($invoiceId > 0) {
                        $this->paymentModel->markInvoiceUnpaid($invoiceId);
                    }
                    $this->rentalModel->retreatDueDate($rentalId);

                    $fresh = $this->rentalModel->findByIdWithRoom($rentalId);
                    $newDue = (string) ($fresh['jatuh_tempo'] ?? '');
                    if ($newDue !== '' && $newDue !== '0000-00-00') {
                        $this->paymentModel->deleteUnpaidInvoicesAfter($rentalId, $newDue);
                    }

                    set_flash('success', 'Status dikembalikan menjadi BELUM BAYAR.');
                }
            } elseif ($action === 'hentikan') {
                // Penyewa menunggak dan admin memilih menghentikan sewa (bukan melunasi).
                if ($isPendingBooking) {
                    set_flash('error', 'Booking yang belum aktif tidak bisa dihentikan. Gunakan Batalkan.');
                    $db->rollback();
                    redirect_to('/admin/dashboard?tab=pembayaran');
                }

                $this->rentalModel->stopActiveByRentalId($rentalId);
                $this->roomModel->setStatus((int) $rental['id_kamar'], 'Tersedia');
                set_flash('success', 'Sewa dihentikan. Kamar dilepas kembali menjadi Tersedia.');
            } else {
                $db->rollback();
                set_flash('error', 'Aksi pembayaran tidak valid.');
                redirect_to('/admin/dashboard?tab=pembayaran');
            }

            $db->commit();
        } catch (\Throwable $throwable) {
            $db->rollback();
            set_flash('error', 'Gagal memperbarui pembayaran.');
        }

        redirect_to('/admin/dashboard?tab=pembayaran');
    }

    public function deleteBooking(): void
    {
        $this->requireAdmin();

        $rentalId = (int) ($_POST['id'] ?? 0);
        $rental = $this->rentalModel->findByIdWithRoom($rentalId);

        if ($rental === null || ($rental['status_sewa'] ?? '') !== 'Dibatalkan') {
            set_flash('error', 'Hanya booking yang dibatalkan yang bisa dihapus.');
            redirect_to('/admin/dashboard');
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $this->paymentModel->deleteByRentalId($rentalId);
            $this->rentalModel->deleteById($rentalId);
            $db->commit();
            set_flash('success', 'Booking dibatalkan berhasil dihapus.');
        } catch (\Throwable $e) {
            $db->rollback();
            set_flash('error', 'Gagal menghapus booking.');
        }

        redirect_to('/admin/dashboard');
    }
}
