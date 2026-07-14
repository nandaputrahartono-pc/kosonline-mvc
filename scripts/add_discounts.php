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

foreach ([
    'kost' => 'foto_kost',
    'kamar' => 'status',
] as $table => $afterColumn) {
    if ($hasColumn($table, 'diskon_persen')) {
        continue;
    }

    $changes[] = "{$table}.diskon_persen";

    if ($applyChanges) {
        $db->execute(
            "ALTER TABLE `{$table}`
             ADD COLUMN `diskon_persen` TINYINT UNSIGNED NOT NULL DEFAULT 0
             AFTER `{$afterColumn}`"
        );
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'columns_added' => $changes,
], JSON_PRETTY_PRINT) . PHP_EOL;
