<?php

declare(strict_types=1);

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

$hasTable = static function (string $table) use ($db): bool {
    return $db->selectOne(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$table]
    ) !== null;
};

if (!$hasColumn('kamar', 'deskripsi_kamar')) {
    $changes[] = 'kamar.deskripsi_kamar';

    if ($applyChanges) {
        $db->execute(
            'ALTER TABLE `kamar`
             ADD COLUMN `deskripsi_kamar` TEXT NULL
             AFTER `fasilitas`'
        );
        $db->execute(
            'UPDATE kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             SET kamar.deskripsi_kamar = kost.deskripsi
             WHERE kamar.deskripsi_kamar IS NULL'
        );
    }
}

if (!$hasTable('galeri_kamar')) {
    $changes[] = 'table:galeri_kamar';

    if ($applyChanges) {
        $db->execute(
            "CREATE TABLE `galeri_kamar` (
                `id_galeri` INT NOT NULL AUTO_INCREMENT,
                `id_kamar` INT NOT NULL,
                `kategori` VARCHAR(60) NOT NULL DEFAULT 'Lainnya',
                `judul` VARCHAR(120) NULL,
                `nama_file` VARCHAR(255) NOT NULL,
                `urutan` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_galeri`),
                KEY `idx_galeri_kamar_urutan` (`id_kamar`, `urutan`),
                CONSTRAINT `galeri_kamar_ibfk_1`
                    FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'changes' => $changes,
], JSON_PRETTY_PRINT) . PHP_EOL;
