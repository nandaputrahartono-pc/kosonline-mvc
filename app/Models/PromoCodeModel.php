<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PromoCodeModel extends Model
{
    public function getActivePublic(): array
    {
        return $this->db->selectAll(
            "SELECT kode, nama_promo, tipe_diskon, nilai_diskon, minimal_transaksi, kuota, digunakan, selesai
             FROM promo_codes
             WHERE aktif = 1
             AND (mulai IS NULL OR mulai <= CURDATE())
             AND (selesai IS NULL OR selesai >= CURDATE())
             AND (kuota IS NULL OR digunakan < kuota)
             ORDER BY minimal_transaksi ASC, nilai_diskon DESC"
        );
    }

    public function findValid(string $code, float $subtotal): ?array
    {
        $normalizedCode = strtoupper(trim($code));
        if ($normalizedCode === '') {
            return null;
        }

        $promo = $this->db->selectOne(
            "SELECT *
             FROM promo_codes
             WHERE kode = ?
             AND aktif = 1
             AND minimal_transaksi <= ?
             AND (mulai IS NULL OR mulai <= CURDATE())
             AND (selesai IS NULL OR selesai >= CURDATE())
             AND (kuota IS NULL OR digunakan < kuota)",
            [$normalizedCode, $subtotal]
        );

        return $promo ?: null;
    }

    public function calculateDiscount(array $promo, float $subtotal): float
    {
        if (($promo['tipe_diskon'] ?? '') === 'nominal') {
            return min($subtotal, max(0, (float) ($promo['nilai_diskon'] ?? 0)));
        }

        $percentage = max(0, min(100, (float) ($promo['nilai_diskon'] ?? 0)));

        return min($subtotal, $subtotal * ($percentage / 100));
    }

    public function incrementUsage(string $code): void
    {
        $this->db->execute(
            "UPDATE promo_codes SET digunakan = digunakan + 1 WHERE kode = ?",
            [strtoupper(trim($code))]
        );
    }

    /**
     * Kembalikan kuota saat booking DIBATALKAN (belum pernah aktif).
     *
     * Tanpa ini kuota bocor: tiap booking batal tetap membakar satu jatah selamanya,
     * sehingga kode promo bisa habis padahal tak ada yang benar-benar memakainya.
     * GREATEST menjaga agar penghitungnya tak pernah minus.
     */
    public function decrementUsage(?string $code): void
    {
        $normalizedCode = strtoupper(trim((string) $code));
        if ($normalizedCode === '') {
            return;
        }

        $this->db->execute(
            "UPDATE promo_codes SET digunakan = GREATEST(digunakan - 1, 0) WHERE kode = ?",
            [$normalizedCode]
        );
    }
}
