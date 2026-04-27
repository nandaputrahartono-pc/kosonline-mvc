<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class KostModel extends Model
{
    public function getRecommendations(int $limit = 5): array
    {
        return $this->db->selectAll(
            "SELECT kamar.*, kost.nama_kost, kost.alamat, kost.foto_kost
             FROM kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE kamar.status = 'Tersedia'
             LIMIT ?",
            [$limit]
        );
    }

    public function getAll(): array
    {
        return $this->db->selectAll("SELECT * FROM kost ORDER BY nama_kost ASC");
    }

    public function findById(int $id): ?array
    {
        return $this->db->selectOne("SELECT * FROM kost WHERE id_kost = ?", [$id]);
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO kost (nama_kost, alamat, deskripsi, foto_kost) VALUES (?, ?, ?, ?)",
            [$data['nama_kost'], $data['alamat'], $data['deskripsi'], $data['foto_kost']]
        );
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['foto_kost'])) {
            return $this->db->execute(
                "UPDATE kost SET nama_kost = ?, alamat = ?, deskripsi = ?, foto_kost = ? WHERE id_kost = ?",
                [$data['nama_kost'], $data['alamat'], $data['deskripsi'], $data['foto_kost'], $id]
            );
        }

        return $this->db->execute(
            "UPDATE kost SET nama_kost = ?, alamat = ?, deskripsi = ? WHERE id_kost = ?",
            [$data['nama_kost'], $data['alamat'], $data['deskripsi'], $id]
        );
    }

    public function updateLocation(int $id, string $latitude, string $longitude): bool
    {
        return $this->db->execute(
            "UPDATE kost SET latitude = ?, longitude = ? WHERE id_kost = ?",
            [$latitude, $longitude, $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM kost WHERE id_kost = ?", [$id]);
    }
}
