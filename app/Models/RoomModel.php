<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RoomModel extends Model
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

    public function searchAvailableFiltered(?string $keyword = null, ?int $idKost = null): array
    {
        $hasKamarDiskon = $this->hasColumn('kamar', 'diskon_persen');
        $hasKostDiskon = $this->hasColumn('kost', 'diskon_persen');

        $kamarDiskonSelect = $hasKamarDiskon ? "kamar.diskon_persen AS kamar_diskon" : "0 AS kamar_diskon";
        $kostDiskonSelect = $hasKostDiskon ? "kost.diskon_persen AS kost_diskon" : "0 AS kost_diskon";

        $conditions = ["kamar.status = 'Tersedia'"];
        $params = [];

        if ($keyword !== null && $keyword !== '') {
            $conditions[] = "(kost.nama_kost LIKE ? OR kost.alamat LIKE ? OR kamar.fasilitas LIKE ?)";
            $likeKeyword = '%' . $keyword . '%';
            $params[] = $likeKeyword;
            $params[] = $likeKeyword;
            $params[] = $likeKeyword;
        }

        if ($idKost !== null && $idKost > 0) {
            $conditions[] = "kost.id_kost = ?";
            $params[] = $idKost;
        }

        $whereClause = implode(" AND ", $conditions);

        $sql = "SELECT kamar.*, kost.nama_kost, kost.alamat, kost.foto_kost, $kamarDiskonSelect, $kostDiskonSelect
                FROM kamar
                JOIN kost ON kamar.id_kost = kost.id_kost
                WHERE $whereClause
                ORDER BY kost.nama_kost ASC, kamar.nomor_kamar ASC";

        $results = $this->db->selectAll($sql, $params);

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

    public function findByIdWithKost(int $id): ?array
    {
        $hasKamarDiskon = $this->hasColumn('kamar', 'diskon_persen');
        $hasKostDiskon = $this->hasColumn('kost', 'diskon_persen');

        $kamarDiskonSelect = $hasKamarDiskon ? "kamar.diskon_persen AS kamar_diskon" : "0 AS kamar_diskon";
        $kostDiskonSelect = $hasKostDiskon ? "kost.diskon_persen AS kost_diskon" : "0 AS kost_diskon";

        $sql = "SELECT kamar.*, kost.nama_kost, kost.alamat, kost.foto_kost, kost.deskripsi AS deskripsi_kost, kost.latitude, kost.longitude, $kamarDiskonSelect, $kostDiskonSelect
                FROM kamar
                JOIN kost ON kamar.id_kost = kost.id_kost
                WHERE kamar.id_kamar = ?";

        $row = $this->db->selectOne($sql, [$id]);

        if ($row !== null) {
            $row['diskon_persen'] = 0;
            if ($hasKamarDiskon && $row['kamar_diskon'] > 0) {
                $row['diskon_persen'] = (int)$row['kamar_diskon'];
            } elseif ($hasKostDiskon && $row['kost_diskon'] > 0) {
                $row['diskon_persen'] = (int)$row['kost_diskon'];
            }
        }

        return $row;
    }

    public function searchAvailable(?string $keyword = null): array
    {
        return $this->searchAvailableFiltered($keyword);
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
            "INSERT INTO kamar (id_kost, nomor_kamar, lantai, fasilitas, deskripsi_kamar, harga, status, diskon_persen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['id_kost'],
                $data['nomor_kamar'],
                $data['lantai'],
                $data['fasilitas'],
                $data['deskripsi_kamar'],
                $data['harga'],
                $data['status'],
                $data['diskon_persen'],
            ]
        );
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->execute(
            "UPDATE kamar
             SET id_kost = ?, nomor_kamar = ?, lantai = ?, fasilitas = ?, deskripsi_kamar = ?, harga = ?, status = ?, diskon_persen = ?
             WHERE id_kamar = ?",
            [
                $data['id_kost'],
                $data['nomor_kamar'],
                $data['lantai'],
                $data['fasilitas'],
                $data['deskripsi_kamar'],
                $data['harga'],
                $data['status'],
                $data['diskon_persen'],
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

    public function setStatus(int $id, string $status): bool
    {
        return $this->db->execute(
            "UPDATE kamar SET status = ? WHERE id_kamar = ?",
            [$status, $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM kamar WHERE id_kamar = ?", [$id]);
    }
}
