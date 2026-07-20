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

if (!$hasColumn('pesan', 'dibaca')) {
    $changes[] = 'pesan.dibaca';

    if ($applyChanges) {
        $db->execute(
            "ALTER TABLE `pesan`
             ADD COLUMN `dibaca` TINYINT(1) NOT NULL DEFAULT 0
             AFTER `isi_pesan`"
        );
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'columns_added' => $changes,
    'note' => $changes === [] ? 'Tidak ada perubahan (kolom sudah ada).' : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
