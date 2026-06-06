<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class UserModel extends Model
{
    public function findByCredentials(string $identifier, string $password): ?array
    {
        $user = $this->findByIdentifier($identifier);

        if ($user === null || !$this->isPasswordValid($user, $password)) {
            return null;
        }

        if (password_needs_rehash((string) $user['password'], PASSWORD_DEFAULT)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $this->db->execute("UPDATE users SET password = ? WHERE id_user = ?", [$hash, $user['id_user']]);
            $user['password'] = $hash;
        }

        return $user;
    }

    public function findByIdentifier(string $identifier): ?array
    {
        $identifier = strtolower(trim($identifier));

        return $this->db->selectOne(
            "SELECT * FROM users WHERE LOWER(email) = ? OR LOWER(username) = ?",
            [$identifier, $identifier]
        );
    }

    public function isPasswordValid(array $user, string $password): bool
    {
        return $this->passwordMatches($password, (string) ($user['password'] ?? ''));
    }

    public function getAll(): array
    {
        return $this->db->selectAll(
            "SELECT id_user, nama_lengkap, username, email, no_hp, foto_profil
             FROM users
             ORDER BY nama_lengkap ASC"
        );
    }

    public function getAvailableForRental(?int $currentUserId = null): array
    {
        if ($currentUserId !== null && $currentUserId > 0) {
            return $this->db->selectAll(
                "SELECT id_user, nama_lengkap, username, email, no_hp, foto_profil
                 FROM users
                 WHERE id_user = ?
                 OR id_user NOT IN (SELECT id_user FROM sewa WHERE status_sewa = 'Aktif')
                 ORDER BY nama_lengkap ASC",
                [$currentUserId]
            );
        }

        return $this->db->selectAll(
            "SELECT id_user, nama_lengkap, username, email, no_hp, foto_profil
             FROM users
             WHERE id_user NOT IN (SELECT id_user FROM sewa WHERE status_sewa = 'Aktif')
             ORDER BY nama_lengkap ASC"
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT id_user, nama_lengkap, username, email, no_hp, foto_profil FROM users WHERE id_user = ?",
            [$id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            "SELECT id_user, nama_lengkap, username, email, no_hp, foto_profil FROM users WHERE email = ?",
            [strtolower(trim($email))]
        );
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

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $username = strtolower(trim($username));

        if ($exceptId !== null) {
            $row = $this->db->selectOne(
                "SELECT id_user FROM users WHERE LOWER(username) = ? AND id_user != ?",
                [$username, $exceptId]
            );
        } else {
            $row = $this->db->selectOne("SELECT id_user FROM users WHERE LOWER(username) = ?", [$username]);
        }

        return $row !== null;
    }

    public function create(array $data): int
    {
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        if ($username === '') {
            $username = $this->makeUsername((string) $data['nama_lengkap'], (string) $data['email']);
        }

        return $this->db->insert(
            "INSERT INTO users (nama_lengkap, username, email, password, no_hp, foto_profil) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['nama_lengkap'],
                $username,
                $data['email'],
                password_hash((string) $data['password'], PASSWORD_DEFAULT),
                $data['no_hp'],
                $data['foto_profil'],
            ]
        );
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            return $this->db->execute(
                "UPDATE users SET nama_lengkap = ?, username = ?, email = ?, no_hp = ?, password = ? WHERE id_user = ?",
                [
                    $data['nama_lengkap'],
                    $data['username'],
                    $data['email'],
                    $data['no_hp'],
                    password_hash((string) $data['password'], PASSWORD_DEFAULT),
                    $id,
                ]
            );
        }

        return $this->db->execute(
            "UPDATE users SET nama_lengkap = ?, username = ?, email = ?, no_hp = ? WHERE id_user = ?",
            [$data['nama_lengkap'], $data['username'], $data['email'], $data['no_hp'], $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM users WHERE id_user = ?", [$id]);
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if (password_get_info($storedPassword)['algo'] !== null) {
            return password_verify($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }

    private function makeUsername(string $name, string $email): string
    {
        $base = explode('@', $email)[0] ?: $name;
        $base = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $base));
        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $suffix = 1;
        while ($this->usernameExists($username)) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }
}
