<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PaymentModel extends Model
{
    public function unpaidCount(string $billingMonth): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total
             FROM sewa
             WHERE status_sewa = 'Aktif'
             AND id_sewa NOT IN (
                 SELECT id_sewa FROM pembayaran WHERE bulan_tagihan = ? AND status_verifikasi = 'Lunas'
             )",
            [$billingMonth]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function totalPaidRevenue(): float
    {
        $row = $this->db->selectOne(
            "SELECT COALESCE(SUM(nominal), 0) AS total
             FROM pembayaran
             WHERE status_verifikasi = 'Lunas'"
        );

        return (float) ($row['total'] ?? 0);
    }

    public function paidRevenueForBillingMonth(string $billingMonth): float
    {
        $row = $this->db->selectOne(
            "SELECT COALESCE(SUM(nominal), 0) AS total
             FROM pembayaran
             WHERE bulan_tagihan = ?
             AND status_verifikasi = 'Lunas'",
            [$billingMonth]
        );

        return (float) ($row['total'] ?? 0);
    }

    public function monthlyRevenueTrend(int $months = 6): array
    {
        $months = max(1, min(12, $months));
        $start = new \DateTimeImmutable('first day of -' . ($months - 1) . ' months');
        $startDate = $start->format('Y-m-d');

        $rows = $this->db->selectAll(
            "SELECT DATE_FORMAT(tanggal_bayar, '%Y-%m') AS month_key,
                    COALESCE(SUM(nominal), 0) AS total
             FROM pembayaran
             WHERE status_verifikasi = 'Lunas'
             AND tanggal_bayar IS NOT NULL
             AND tanggal_bayar >= ?
             GROUP BY month_key
             ORDER BY month_key ASC",
            [$startDate]
        );

        $totalsByMonth = [];
        foreach ($rows as $row) {
            $totalsByMonth[(string) $row['month_key']] = (float) $row['total'];
        }

        $labels = [];
        $values = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->modify('+' . $i . ' months');
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $values[] = $totalsByMonth[$key] ?? 0.0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
            'max' => $values === [] ? 0.0 : max($values),
        ];
    }

    public function markPaid(int $rentalId, string $billingMonth, float $amount): void
    {
        $today = date('Y-m-d');
        $existing = $this->db->selectOne(
            "SELECT id_pembayaran FROM pembayaran WHERE id_sewa = ? AND bulan_tagihan = ?",
            [$rentalId, $billingMonth]
        );

        if ($existing !== null) {
            $this->db->execute(
                "UPDATE pembayaran
                 SET status_verifikasi = 'Lunas', tanggal_bayar = ?, nominal = ?
                 WHERE id_sewa = ? AND bulan_tagihan = ?",
                [$today, $amount, $rentalId, $billingMonth]
            );
            return;
        }

        $this->db->insert(
            "INSERT INTO pembayaran (id_sewa, bulan_tagihan, tanggal_bayar, nominal, status_verifikasi)
             VALUES (?, ?, ?, ?, 'Lunas')",
            [$rentalId, $billingMonth, $today, $amount]
        );
    }

    public function createPendingInvoice(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO pembayaran (
                id_sewa, invoice_no, bulan_tagihan, periode_mulai, periode_selesai,
                tanggal_bayar, nominal, harga_kamar, diskon_kamar, kode_promo,
                diskon_promo, biaya_admin, deposit, total_bayar, bukti_bayar,
                metode_bayar, nama_penyewa, email_penyewa, no_hp_penyewa,
                catatan, status_verifikasi
             ) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, 'Menunggu')",
            [
                $data['id_sewa'],
                $data['invoice_no'],
                $data['bulan_tagihan'],
                $data['periode_mulai'],
                $data['periode_selesai'],
                $data['total_bayar'],
                $data['harga_kamar'],
                $data['diskon_kamar'],
                $data['kode_promo'],
                $data['diskon_promo'],
                $data['biaya_admin'],
                $data['deposit'],
                $data['total_bayar'],
                $data['metode_bayar'],
                $data['nama_penyewa'],
                $data['email_penyewa'],
                $data['no_hp_penyewa'],
                $data['catatan'],
            ]
        );
    }

    public function findInvoiceById(int $paymentId): ?array
    {
        return $this->db->selectOne(
            "SELECT pembayaran.*, sewa.id_user, sewa.kode_booking, sewa.tanggal_masuk, sewa.jatuh_tempo, sewa.status_sewa,
                    kamar.id_kamar, kamar.nomor_kamar, kamar.lantai, kamar.fasilitas,
                    kost.nama_kost, kost.alamat, kost.foto_kost
             FROM pembayaran
             JOIN sewa ON pembayaran.id_sewa = sewa.id_sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE pembayaran.id_pembayaran = ?",
            [$paymentId]
        );
    }

    /**
     * Ambil invoice hanya jika benar milik user yang bersangkutan.
     * Dipakai untuk validasi sebelum user mengupload bukti transfer.
     */
    public function findInvoiceForUser(int $paymentId, int $userId): ?array
    {
        $invoice = $this->findInvoiceById($paymentId);

        if ($invoice === null || (int) ($invoice['id_user'] ?? 0) !== $userId) {
            return null;
        }

        return $invoice;
    }

    /**
     * Simpan nama file bukti transfer dan kembalikan status verifikasi ke "Menunggu"
     * supaya admin tahu ada bukti baru untuk dicek (termasuk saat sebelumnya Ditolak).
     */
    public function attachProof(int $paymentId, string $filename): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET bukti_bayar = ?, status_verifikasi = 'Menunggu'
             WHERE id_pembayaran = ?",
            [$filename, $paymentId]
        );
    }

    public function markPaidByRental(int $rentalId, float $amount): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET status_verifikasi = 'Lunas', tanggal_bayar = ?, nominal = ?, total_bayar = ?
             WHERE id_sewa = ?
             ORDER BY id_pembayaran DESC
             LIMIT 1",
            [date('Y-m-d'), $amount, $amount, $rentalId]
        );
    }

    public function rejectLatestByRental(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET status_verifikasi = 'Ditolak'
             WHERE id_sewa = ?
             ORDER BY id_pembayaran DESC
             LIMIT 1",
            [$rentalId]
        );
    }

    public function cancelPayment(int $rentalId, string $billingMonth): void
    {
        $this->db->execute(
            "DELETE FROM pembayaran WHERE id_sewa = ? AND bulan_tagihan = ?",
            [$rentalId, $billingMonth]
        );
    }

    public function getHistoryByRentalId(int $rentalId): array
    {
        return $this->db->selectAll(
            "SELECT * FROM pembayaran WHERE id_sewa = ? ORDER BY id_pembayaran DESC",
            [$rentalId]
        );
    }

    public function getHistoryByUserId(int $userId): array
    {
        return $this->db->selectAll(
            "SELECT pembayaran.*, sewa.kode_booking, sewa.status_sewa,
                    kamar.nomor_kamar, kamar.lantai,
                    kost.nama_kost
             FROM pembayaran
             JOIN sewa ON pembayaran.id_sewa = sewa.id_sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE sewa.id_user = ?
             ORDER BY pembayaran.id_pembayaran DESC",
            [$userId]
        );
    }

    public function deleteByRoomId(int $roomId): void
    {
        $this->db->execute(
            "DELETE FROM pembayaran WHERE id_sewa IN (SELECT id_sewa FROM sewa WHERE id_kamar = ?)",
            [$roomId]
        );
    }

    public function deleteByRentalId(int $rentalId): void
    {
        $this->db->execute("DELETE FROM pembayaran WHERE id_sewa = ?", [$rentalId]);
    }
}
