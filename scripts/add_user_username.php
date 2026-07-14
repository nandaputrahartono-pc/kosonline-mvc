<?php

declare(strict_types=1);

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Skrip ini hanya boleh dijalankan lewat terminal (CLI).");
}

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$applyChanges = in_array('--apply', $argv, true);
$db = Database::getInstance();
$changes = [];

$hasColumn = static function (string $table, string $column) use ($db): bool {
    foreach ($db->selectAll("DESCRIBE `{$table}`") as $definition) {
        if (strcasecmp((string) $definition['Field'], $column) === 0) {
            return true;
        }
    }

    return false;
};

$hasIndex = static function (string $table, string $index) use ($db): bool {
    return $db->selectOne(
        "SELECT INDEX_NAME
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = ?
         AND INDEX_NAME = ?",
        [$table, $index]
    ) !== null;
};

$makeBaseUsername = static function (string $name, string $email): string {
    $base = explode('@', $email)[0] ?: $name;
    $base = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $base));

    return $base !== '' ? $base : 'user';
};

if (!$hasColumn('users', 'username')) {
    $changes[] = 'users.username';

    if ($applyChanges) {
        $db->execute("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(50) NULL AFTER `email`");
    }
}

if ($applyChanges && $hasColumn('users', 'username')) {
    $taken = [];
    foreach ($db->selectAll("SELECT username FROM users WHERE username IS NOT NULL AND username != ''") as $row) {
        $taken[strtolower((string) $row['username'])] = true;
    }

    $users = $db->selectAll(
        "SELECT id_user, nama_lengkap, email, username
         FROM users
         WHERE username IS NULL OR username = ''
         ORDER BY id_user ASC"
    );

    foreach ($users as $user) {
        $base = $makeBaseUsername((string) $user['nama_lengkap'], (string) $user['email']);
        $username = $base;
        $suffix = (int) $user['id_user'];

        while (isset($taken[$username])) {
            $username = $base . $suffix;
            $suffix++;
        }

        $taken[$username] = true;
        $db->execute("UPDATE users SET username = ? WHERE id_user = ?", [$username, $user['id_user']]);
    }
}

if ($hasColumn('users', 'username') && !$hasIndex('users', 'idx_users_username')) {
    $changes[] = 'index:users.username';

    if ($applyChanges) {
        $db->execute("ALTER TABLE `users` ADD UNIQUE KEY `idx_users_username` (`username`)");
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'changes' => array_values(array_unique($changes)),
], JSON_PRETTY_PRINT) . PHP_EOL;
