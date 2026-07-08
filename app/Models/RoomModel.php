<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RoomModel extends Model
{
    /** @var array<string, bool> */
    private static array $columnCache = [];

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = strtolower($table . '.' . $column);
        if (array_key_exists($cacheKey, self::$columnCache)) {
            return self::$columnCache[$cacheKey];
        }

        try {
            $columns = $this->db->selectAll("DESCRIBE `$table`");
            foreach ($columns as $col) {
                if (strtolower($col['Field']) === strtolower($column)) {
                    self::$columnCache[$cacheKey] = true;
                    return self::$columnCache[$cacheKey];
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        self::$columnCache[$cacheKey] = false;
        return self::$columnCache[$cacheKey];
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

    public function searchAvailableFilteredPaginated(
        ?string $keyword = null,
        ?int $idKost = null,
        bool $promoOnly = false,
        string $sort = 'recommended',
        int $limit = 9,
        int $offset = 0
    ): array {
        $limit = max(1, min(48, $limit));
        $offset = max(0, $offset);
        $hasKamarDiskon = $this->hasColumn('kamar', 'diskon_persen');
        $hasKostDiskon = $this->hasColumn('kost', 'diskon_persen');

        $kamarDiskonExpr = $hasKamarDiskon ? 'COALESCE(kamar.diskon_persen, 0)' : '0';
        $kostDiskonExpr = $hasKostDiskon ? 'COALESCE(kost.diskon_persen, 0)' : '0';
        $effectiveDiscountExpr = "CASE WHEN {$kamarDiskonExpr} > 0 THEN {$kamarDiskonExpr} WHEN {$kostDiskonExpr} > 0 THEN {$kostDiskonExpr} ELSE 0 END";
        [$whereClause, $params] = $this->availableWhereClause($keyword, $idKost, $promoOnly, $effectiveDiscountExpr);

        $orderBy = match ($sort) {
            'termurah' => "ORDER BY (COALESCE(kamar.harga, 0) * (1 - (($effectiveDiscountExpr) / 100))) ASC, kost.nama_kost ASC, kamar.nomor_kamar ASC",
            'termahal' => "ORDER BY (COALESCE(kamar.harga, 0) * (1 - (($effectiveDiscountExpr) / 100))) DESC, kost.nama_kost ASC, kamar.nomor_kamar ASC",
            'promo' => "ORDER BY ($effectiveDiscountExpr) DESC, kost.nama_kost ASC, kamar.nomor_kamar ASC",
            default => "ORDER BY kost.nama_kost ASC, kamar.nomor_kamar ASC",
        };

        $sql = "SELECT kamar.*, kost.nama_kost, kost.alamat, kost.foto_kost,
                       {$kamarDiskonExpr} AS kamar_diskon,
                       {$kostDiskonExpr} AS kost_diskon
                FROM kamar
                JOIN kost ON kamar.id_kost = kost.id_kost
                WHERE {$whereClause}
                {$orderBy}
                LIMIT {$limit} OFFSET {$offset}";

        $results = $this->db->selectAll($sql, $params);
        $this->applyEffectiveDiscount($results, $hasKamarDiskon, $hasKostDiskon);

        return $results;
    }

    public function countAvailableFiltered(?string $keyword = null, ?int $idKost = null, bool $promoOnly = false): int
    {
        $hasKamarDiskon = $this->hasColumn('kamar', 'diskon_persen');
        $hasKostDiskon = $this->hasColumn('kost', 'diskon_persen');
        $kamarDiskonExpr = $hasKamarDiskon ? 'COALESCE(kamar.diskon_persen, 0)' : '0';
        $kostDiskonExpr = $hasKostDiskon ? 'COALESCE(kost.diskon_persen, 0)' : '0';
        $effectiveDiscountExpr = "CASE WHEN {$kamarDiskonExpr} > 0 THEN {$kamarDiskonExpr} WHEN {$kostDiskonExpr} > 0 THEN {$kostDiskonExpr} ELSE 0 END";
        [$whereClause, $params] = $this->availableWhereClause($keyword, $idKost, $promoOnly, $effectiveDiscountExpr);

        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total
             FROM kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE {$whereClause}",
            $params
        );

        return (int) ($row['total'] ?? 0);
    }

    public function availableSummary(): array
    {
        $hasKamarDiskon = $this->hasColumn('kamar', 'diskon_persen');
        $hasKostDiskon = $this->hasColumn('kost', 'diskon_persen');
        $kamarDiskonExpr = $hasKamarDiskon ? 'COALESCE(kamar.diskon_persen, 0)' : '0';
        $kostDiskonExpr = $hasKostDiskon ? 'COALESCE(kost.diskon_persen, 0)' : '0';
        $effectiveDiscountExpr = "CASE WHEN {$kamarDiskonExpr} > 0 THEN {$kamarDiskonExpr} WHEN {$kostDiskonExpr} > 0 THEN {$kostDiskonExpr} ELSE 0 END";

        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total_available,
                    SUM(CASE WHEN {$effectiveDiscountExpr} > 0 THEN 1 ELSE 0 END) AS promo_count,
                    MIN(COALESCE(kamar.harga, 0) * (1 - (({$effectiveDiscountExpr}) / 100))) AS lowest_price
             FROM kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE kamar.status = 'Tersedia'"
        );

        return [
            'total_available' => (int) ($row['total_available'] ?? 0),
            'promo_count' => (int) ($row['promo_count'] ?? 0),
            'lowest_price' => (float) ($row['lowest_price'] ?? 0),
        ];
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

    public function getAllForAdminPaginated(int $limit = 10, int $offset = 0): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        return $this->db->selectAll(
            "SELECT kamar.*, kost.nama_kost
             FROM kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             ORDER BY kost.nama_kost ASC, kamar.nomor_kamar ASC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public function countAllForAdmin(): int
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS total FROM kamar");

        return (int) ($row['total'] ?? 0);
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

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function availableWhereClause(
        ?string $keyword,
        ?int $idKost,
        bool $promoOnly,
        string $effectiveDiscountExpr
    ): array {
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

        if ($promoOnly) {
            $conditions[] = "({$effectiveDiscountExpr}) > 0";
        }

        return [implode(' AND ', $conditions), $params];
    }

    private function applyEffectiveDiscount(array &$rows, bool $hasKamarDiskon, bool $hasKostDiskon): void
    {
        foreach ($rows as &$row) {
            $row['diskon_persen'] = 0;
            if ($hasKamarDiskon && (int) ($row['kamar_diskon'] ?? 0) > 0) {
                $row['diskon_persen'] = (int) $row['kamar_diskon'];
            } elseif ($hasKostDiskon && (int) ($row['kost_diskon'] ?? 0) > 0) {
                $row['diskon_persen'] = (int) $row['kost_diskon'];
            }
        }
        unset($row);
    }
}
