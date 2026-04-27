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

    public function deleteByRoomId(int $roomId): void
    {
        $this->db->execute(
            "DELETE FROM pembayaran WHERE id_sewa IN (SELECT id_sewa FROM sewa WHERE id_kamar = ?)",
            [$roomId]
        );
    }
}
