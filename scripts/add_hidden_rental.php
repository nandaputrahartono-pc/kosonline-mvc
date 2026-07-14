<?php

declare(strict_types=1);

/**
 * Kolom penanda "disembunyikan dari daftar user".
 *
 * Saat user menghapus kartu sewa yang sudah berakhir/dibatalkan, kartunya hilang dari
 * daftar "Pesanan & Invoice" TAPI catatan pembayarannya TIDAK dihapus — supaya Total
 * Pendapatan admin & riwayat keuangan tetap utuh (user tak bisa "menghapus" uang yang
 * sudah masuk).
 *
 * Pakai:
 *   php scripts/add_hidden_rental.php           # dry-run
 *   php scripts/add_hidden_rental.php --apply   # terapkan
 *
 * Idempotent: aman dijalankan berkali-kali.
 */

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

if (!$hasColumn('sewa', 'disembunyikan')) {
    $changes[] = 'sewa.disembunyikan';

    if ($applyChanges) {
        $db->execute(
            "ALTER TABLE `sewa`
             ADD COLUMN `disembunyikan` TINYINT(1) NOT NULL DEFAULT 0
             AFTER `status_sewa`"
        );
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'columns_added' => $changes,
    'note' => $changes === [] ? 'Tidak ada perubahan (kolom sudah ada).' : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
