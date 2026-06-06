<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class KostModel extends Model
{
    private function hasColumn(string $table, string $column): bool
    {
        try {
            $columns = $this->db->selectAll("DESCRIBE `$table`");
            foreach ($columns as $col) {
                if (strtolower($col['Field']) === strtolower($column)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }

    public function getRecommendations(int $limit = 6): array
    {
        $limit = max(1, min($limit, 50));
        $hasKamarDiskon = $this->hasColumn('kamar', 'diskon_persen');
        $hasKostDiskon = $this->hasColumn('kost', 'diskon_persen');

        $kamarDiskonSelect = $hasKamarDiskon ? "kamar.diskon_persen AS kamar_diskon" : "0 AS kamar_diskon";
        $kostDiskonSelect = $hasKostDiskon ? "kost.diskon_persen AS kost_diskon" : "0 AS kost_diskon";

        $sql = "SELECT kamar.*, kost.nama_kost, kost.alamat, kost.foto_kost, $kamarDiskonSelect, $kostDiskonSelect
                FROM kamar
                JOIN kost ON kamar.id_kost = kost.id_kost
                WHERE kamar.status = 'Tersedia'
                LIMIT $limit";
        
        $results = $this->db->selectAll($sql);
        
        foreach ($results as &$row) {
            $row['diskon_persen'] = 0;
            if ($hasKamarDiskon && $row['kamar_diskon'] > 0) {
                $row['diskon_persen'] = (int)$row['kamar_diskon'];
            } elseif ($hasKostDiskon && $row['kost_diskon'] > 0) {
                $row['diskon_persen'] = (int)$row['kost_diskon'];
            }
        }
        return $results;
    }

    public function getPromos(int $limit = 6): array
    {
        $limit = max(1, min($limit, 50));
        $hasKamarDiskon = $this->hasColumn('kamar', 'diskon_persen');
        $hasKostDiskon = $this->hasColumn('kost', 'diskon_persen');

        $kamarDiskonSelect = $hasKamarDiskon ? "kamar.diskon_persen AS kamar_diskon" : "0 AS kamar_diskon";
        $kostDiskonSelect = $hasKostDiskon ? "kost.diskon_persen AS kost_diskon" : "0 AS kost_diskon";

        $sql = "SELECT kamar.*, kost.nama_kost, kost.alamat, kost.foto_kost, $kamarDiskonSelect, $kostDiskonSelect
                FROM kamar
                JOIN kost ON kamar.id_kost = kost.id_kost
                WHERE kamar.status = 'Tersedia'";
        
        $results = $this->db->selectAll($sql);
        $promos = [];

        foreach ($results as $row) {
            $row['diskon_persen'] = 0;
            if ($hasKamarDiskon && $row['kamar_diskon'] > 0) {
                $row['diskon_persen'] = (int)$row['kamar_diskon'];
            } elseif ($hasKostDiskon && $row['kost_diskon'] > 0) {
                $row['diskon_persen'] = (int)$row['kost_diskon'];
            }

            if ($row['diskon_persen'] > 0) {
                $promos[] = $row;
            }
        }

        return array_slice($promos, 0, $limit);
    }

    public function getAllWithAvailableCount(): array
    {
        return $this->db->selectAll(
            "SELECT kost.*, 
             COUNT(CASE WHEN kamar.status = 'Tersedia' THEN 1 END) AS kamar_tersedia
             FROM kost
             LEFT JOIN kamar ON kost.id_kost = kamar.id_kost
             GROUP BY kost.id_kost
             ORDER BY kost.nama_kost ASC"
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
            "INSERT INTO kost (nama_kost, alamat, deskripsi, foto_kost, diskon_persen) VALUES (?, ?, ?, ?, ?)",
            [$data['nama_kost'], $data['alamat'], $data['deskripsi'], $data['foto_kost'], $data['diskon_persen']]
        );
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['foto_kost'])) {
            return $this->db->execute(
                "UPDATE kost SET nama_kost = ?, alamat = ?, deskripsi = ?, foto_kost = ?, diskon_persen = ? WHERE id_kost = ?",
                [$data['nama_kost'], $data['alamat'], $data['deskripsi'], $data['foto_kost'], $data['diskon_persen'], $id]
            );
        }

        return $this->db->execute(
            "UPDATE kost SET nama_kost = ?, alamat = ?, deskripsi = ?, diskon_persen = ? WHERE id_kost = ?",
            [$data['nama_kost'], $data['alamat'], $data['deskripsi'], $data['diskon_persen'], $id]
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
