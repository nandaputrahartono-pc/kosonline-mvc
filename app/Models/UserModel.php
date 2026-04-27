<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class UserModel extends Model
{
    public function findByCredentials(string $email, string $password): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM users WHERE email = ? AND password = ?",
            [$email, $password]
        );
    }

    public function getAll(): array
    {
        return $this->db->selectAll("SELECT * FROM users ORDER BY nama_lengkap ASC");
    }

    public function getAvailableForRental(?int $currentUserId = null): array
    {
        if ($currentUserId !== null && $currentUserId > 0) {
            return $this->db->selectAll(
                "SELECT *
                 FROM users
                 WHERE id_user = ?
                 OR id_user NOT IN (SELECT id_user FROM sewa WHERE status_sewa = 'Aktif')
                 ORDER BY nama_lengkap ASC",
                [$currentUserId]
            );
        }

        return $this->db->selectAll(
            "SELECT *
             FROM users
             WHERE id_user NOT IN (SELECT id_user FROM sewa WHERE status_sewa = 'Aktif')
             ORDER BY nama_lengkap ASC"
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->selectOne("SELECT * FROM users WHERE id_user = ?", [$id]);
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            $row = $this->db->selectOne(
                "SELECT id_user FROM users WHERE email = ? AND id_user != ?",
                [$email, $exceptId]
            );
        } else {
            $row = $this->db->selectOne("SELECT id_user FROM users WHERE email = ?", [$email]);
        }

        return $row !== null;
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO users (nama_lengkap, email, password, no_hp, foto_profil) VALUES (?, ?, ?, ?, ?)",
            [$data['nama_lengkap'], $data['email'], $data['password'], $data['no_hp'], $data['foto_profil']]
        );
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            return $this->db->execute(
                "UPDATE users SET nama_lengkap = ?, email = ?, no_hp = ?, password = ? WHERE id_user = ?",
                [$data['nama_lengkap'], $data['email'], $data['no_hp'], $data['password'], $id]
            );
        }

        return $this->db->execute(
            "UPDATE users SET nama_lengkap = ?, email = ?, no_hp = ? WHERE id_user = ?",
            [$data['nama_lengkap'], $data['email'], $data['no_hp'], $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM users WHERE id_user = ?", [$id]);
    }
}
