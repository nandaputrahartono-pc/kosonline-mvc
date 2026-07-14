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

$hasTable = static function (string $table) use ($db): bool {
    return $db->selectOne(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$table]
    ) !== null;
};

if (!$hasTable('chat_typing')) {
    $changes[] = 'table:chat_typing';

    if ($applyChanges) {
        $db->execute(
            "CREATE TABLE `chat_typing` (
                `id_thread` INT NOT NULL,
                `actor_type` ENUM('user','admin') NOT NULL,
                `is_typing` TINYINT(1) NOT NULL DEFAULT 0,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_thread`, `actor_type`),
                CONSTRAINT `fk_chat_typing_thread` FOREIGN KEY (`id_thread`) REFERENCES `chat_threads` (`id_thread`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'changes' => $changes,
], JSON_PRETTY_PRINT) . PHP_EOL;
