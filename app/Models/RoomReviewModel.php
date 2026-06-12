<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RoomReviewModel extends Model
{
    public function getByRoomId(int $roomId): array
    {
        return $this->db->selectAll(
            "SELECT room_reviews.*, users.nama_lengkap, users.foto_profil
             FROM room_reviews
             JOIN users ON room_reviews.id_user = users.id_user
             WHERE room_reviews.id_kamar = ?
             ORDER BY room_reviews.dibuat_pada DESC",
            [$roomId]
        );
    }

    public function summaryByRoomId(int $roomId): array
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total_review, COALESCE(AVG(rating), 0) AS rating_avg
             FROM room_reviews
             WHERE id_kamar = ?",
            [$roomId]
        );

        return [
            'total_review' => (int) ($row['total_review'] ?? 0),
            'rating_avg' => round((float) ($row['rating_avg'] ?? 0), 1),
        ];
    }

    public function getSummariesForRooms(array $roomIds): array
    {
        $roomIds = array_values(array_filter(array_map('intval', $roomIds)));
        if ($roomIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
        $rows = $this->db->selectAll(
            "SELECT id_kamar, COUNT(*) AS total_review, COALESCE(AVG(rating), 0) AS rating_avg
             FROM room_reviews
             WHERE id_kamar IN ({$placeholders})
             GROUP BY id_kamar",
            $roomIds
        );

        $summaries = [];
        foreach ($rows as $row) {
            $summaries[(int) $row['id_kamar']] = [
                'total_review' => (int) $row['total_review'],
                'rating_avg' => round((float) $row['rating_avg'], 1),
            ];
        }

        return $summaries;
    }

    public function upsert(int $userId, int $roomId, int $rating, string $comment): void
    {
        $rating = max(1, min(5, $rating));

        $this->db->execute(
            "INSERT INTO room_reviews (id_user, id_kamar, rating, komentar)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), komentar = VALUES(komentar), diubah_pada = CURRENT_TIMESTAMP",
            [$userId, $roomId, $rating, $comment]
        );
    }
}
