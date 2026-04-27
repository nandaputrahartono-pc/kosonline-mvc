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
}
