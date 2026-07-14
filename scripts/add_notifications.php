<?php

declare(strict_types=1);

/**
 * Menyiapkan kolom penanda "sudah dibaca" untuk notifikasi.
 *
 * - `pesan.dibaca`  : penanda pesan kontak sudah dibaca admin (belum ada di skema).
 *
 * Catatan: `chat_messages.dibaca` sudah ada di skema (tinggal dipakai), jadi tak diubah.
 *
 * Pakai:
 *   php scripts/add_notifications.php           # dry-run: hanya melaporkan rencana
 *   php scripts/add_notifications.php --apply   # benar-benar mengubah tabel
 *
 * Idempotent: aman dijalankan berkali-kali.
 */

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
