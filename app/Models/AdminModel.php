<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminModel extends Model
{
    public function findByUsername(string $username): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM admin WHERE username = ?",
            [trim($username)]
        );
    }

    public function findByCredentials(string $username, string $password): ?array
    {
        $admin = $this->findByUsername($username);

        if ($admin === null || !$this->isPasswordValid($admin, $password)) {
            return null;
        }

        if (password_needs_rehash((string) $admin['password'], PASSWORD_DEFAULT)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $this->db->execute("UPDATE admin SET password = ? WHERE id_admin = ?", [$hash, $admin['id_admin']]);
            $admin['password'] = $hash;
        }

        return $admin;
    }

    public function isPasswordValid(array $admin, string $password): bool
    {
        return $this->passwordMatches($password, (string) ($admin['password'] ?? ''));
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if (password_get_info($storedPassword)['algo'] !== null) {
            return password_verify($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }
}
