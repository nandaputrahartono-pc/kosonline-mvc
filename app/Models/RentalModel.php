<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RentalModel extends Model
{
    public function countActive(): int
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS total FROM sewa WHERE status_sewa = 'Aktif'");

        return (int) ($row['total'] ?? 0);
    }

    public function getActiveByUserId(int $userId): ?array
    {
        return $this->db->selectOne(
            "SELECT sewa.*, kamar.nomor_kamar, kamar.harga, kamar.lantai, kamar.fasilitas, kost.nama_kost, kost.alamat, kost.foto_kost
             FROM sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE sewa.id_user = ? AND sewa.status_sewa = 'Aktif'",
            [$userId]
        );
    }

    public function getActiveTenantByRoomId(int $roomId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM sewa WHERE id_kamar = ? AND status_sewa = 'Aktif'",
            [$roomId]
        );
    }

    public function getActiveByUserIdOnly(int $userId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM sewa WHERE id_user = ? AND status_sewa = 'Aktif'",
            [$userId]
        );
    }

    public function createActive(int $userId, int $roomId): int
    {
        return $this->db->insert(
            "INSERT INTO sewa (id_user, id_kamar, tanggal_masuk, status_sewa) VALUES (?, ?, ?, 'Aktif')",
            [$userId, $roomId, date('Y-m-d')]
        );
    }

    public function stopActiveByRentalId(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE sewa SET status_sewa = 'Berhenti', tanggal_keluar = ? WHERE id_sewa = ?",
            [date('Y-m-d'), $rentalId]
        );
    }

    public function stopActiveByRoomId(int $roomId): void
    {
        $this->db->execute(
            "UPDATE sewa SET status_sewa = 'Berhenti', tanggal_keluar = ? WHERE id_kamar = ? AND status_sewa = 'Aktif'",
            [date('Y-m-d'), $roomId]
        );
    }

    public function deleteByRoomId(int $roomId): void
    {
        $this->db->execute("DELETE FROM sewa WHERE id_kamar = ?", [$roomId]);
    }

    public function getAdminBillingRows(string $billingMonth): array
    {
        return $this->db->selectAll(
            "SELECT
                sewa.id_sewa,
                users.nama_lengkap,
                users.no_hp,
                kost.nama_kost,
                kamar.nomor_kamar,
                kamar.harga,
                pembayaran.status_verifikasi
             FROM sewa
             JOIN users ON sewa.id_user = users.id_user
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             LEFT JOIN pembayaran ON sewa.id_sewa = pembayaran.id_sewa AND pembayaran.bulan_tagihan = ?
             WHERE sewa.status_sewa = 'Aktif'",
            [$billingMonth]
        );
    }
}
