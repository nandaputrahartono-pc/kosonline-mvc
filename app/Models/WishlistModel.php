<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WishlistModel extends Model
{
    public function isSaved(int $userId, int $roomId): bool
    {
        return $this->db->selectOne(
            "SELECT id_wishlist FROM room_wishlists WHERE id_user = ? AND id_kamar = ?",
            [$userId, $roomId]
        ) !== null;
    }

    public function toggle(int $userId, int $roomId): bool
    {
        $existing = $this->db->selectOne(
            "SELECT id_wishlist FROM room_wishlists WHERE id_user = ? AND id_kamar = ?",
            [$userId, $roomId]
        );

        if ($existing !== null) {
            $this->db->execute("DELETE FROM room_wishlists WHERE id_wishlist = ?", [$existing['id_wishlist']]);
            return false;
        }

        $this->db->insert(
            "INSERT INTO room_wishlists (id_user, id_kamar) VALUES (?, ?)",
            [$userId, $roomId]
        );

        return true;
    }

    public function getSavedRoomIds(int $userId): array
    {
        $rows = $this->db->selectAll(
            "SELECT id_kamar FROM room_wishlists WHERE id_user = ?",
            [$userId]
        );

        return array_map(static fn(array $row): int => (int) $row['id_kamar'], $rows);
    }

    public function getByUserId(int $userId): array
    {
        $rows = $this->db->selectAll(
            "SELECT room_wishlists.*, kamar.nomor_kamar, kamar.lantai, kamar.harga, kamar.fasilitas,
                    kamar.status, kamar.diskon_persen AS kamar_diskon,
                    kost.nama_kost, kost.alamat, kost.foto_kost, kost.diskon_persen AS diskon_cabang
             FROM room_wishlists
             JOIN kamar ON room_wishlists.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE room_wishlists.id_user = ?
             ORDER BY room_wishlists.dibuat_pada DESC",
            [$userId]
        );

        foreach ($rows as &$row) {
            $roomDiscount = (int) ($row['kamar_diskon'] ?? 0);
            $branchDiscount = (int) ($row['diskon_cabang'] ?? 0);
            $row['diskon_persen'] = $roomDiscount > 0 ? $roomDiscount : $branchDiscount;
        }

        return $rows;
    }
}
