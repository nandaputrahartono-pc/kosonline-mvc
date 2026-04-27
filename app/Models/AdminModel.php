<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminModel extends Model
{
    public function findByCredentials(string $username, string $password): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM admin WHERE username = ? AND password = ?",
            [$username, $password]
        );
    }
}
