<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RoomModel extends Model
{
    public function searchAvailable(?string $keyword = null): array
    {
        if ($keyword !== null && $keyword !== '') {
            $likeKeyword = '%' . $keyword . '%';

            return $this->db->selectAll(
                "SELECT kamar.*, kost.nama_kost, kost.alamat, kost.foto_kost
                 FROM kamar
                 JOIN kost ON kamar.id_kost = kost.id_kost
                 WHERE (kost.nama_kost LIKE ? OR kost.alamat LIKE ?)
                 AND kamar.status = 'Tersedia'
                 ORDER BY kost.nama_kost ASC, kamar.nomor_kamar ASC",
                [$likeKeyword, $likeKeyword]
            );
        }

        return $this->db->selectAll(
            "SELECT kamar.*, kost.nama_kost, kost.alamat, kost.foto_kost
             FROM kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE kamar.status = 'Tersedia'
             ORDER BY kost.nama_kost ASC, kamar.nomor_kamar ASC"
        );
    }

    public function getAllForAdmin(): array
    {
        return $this->db->selectAll(
            "SELECT kamar.*, kost.nama_kost
             FROM kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             ORDER BY kost.nama_kost ASC, kamar.nomor_kamar ASC"
        );
    }

    public function getCounts(): array
    {
        return $this->db->selectOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Terisi' THEN 1 ELSE 0 END) AS terisi,
                SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) AS kosong
             FROM kamar"
        ) ?? ['total' => 0, 'terisi' => 0, 'kosong' => 0];
    }

    public function findById(int $id): ?array
    {
        return $this->db->selectOne("SELECT * FROM kamar WHERE id_kamar = ?", [$id]);
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO kamar (id_kost, nomor_kamar, lantai, fasilitas, harga, status) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['id_kost'],
                $data['nomor_kamar'],
                $data['lantai'],
                $data['fasilitas'],
                $data['harga'],
                $data['status'],
            ]
        );
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->execute(
            "UPDATE kamar
             SET id_kost = ?, nomor_kamar = ?, lantai = ?, fasilitas = ?, harga = ?, status = ?
             WHERE id_kamar = ?",
            [
                $data['id_kost'],
                $data['nomor_kamar'],
                $data['lantai'],
                $data['fasilitas'],
                $data['harga'],
                $data['status'],
                $id,
            ]
        );
    }

    public function toggleStatus(int $id, string $currentStatus): bool
    {
        $newStatus = $currentStatus === 'Tersedia' ? 'Terisi' : 'Tersedia';

        return $this->db->execute(
            "UPDATE kamar SET status = ? WHERE id_kamar = ?",
            [$newStatus, $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM kamar WHERE id_kamar = ?", [$id]);
    }
}
