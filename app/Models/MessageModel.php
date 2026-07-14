<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class MessageModel extends Model
{
    public function getAll(): array
    {
        return $this->db->selectAll("SELECT * FROM pesan ORDER BY id_pesan DESC");
    }

    public function create(string $name, string $email, string $message): int
    {
        return $this->db->insert(
            "INSERT INTO pesan (nama_pengirim, email_pengirim, isi_pesan) VALUES (?, ?, ?)",
            [$name, $email, $message]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM pesan WHERE id_pesan = ?", [$id]);
    }

    /**
     * Jumlah pesan kontak yang belum dibaca admin (untuk lonceng notifikasi).
     */
    public function countUnread(): int
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS total FROM pesan WHERE dibaca = 0");

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Tandai semua pesan kontak sudah dibaca (dipanggil saat admin membuka tab Pesan Masuk).
     */
    public function markAllRead(): void
    {
        $this->db->execute("UPDATE pesan SET dibaca = 1 WHERE dibaca = 0");
    }
}
