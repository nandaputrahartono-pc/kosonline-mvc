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

$column = $db->selectOne("SHOW COLUMNS FROM kamar WHERE Field = 'status'");
$currentType = (string) ($column['Type'] ?? '');

echo "=== Tambah status 'Arsip' pada kamar ===\n";
echo 'Mode      : ' . ($applyChanges ? 'APPLY' : 'DRY-RUN (tidak mengubah apa pun)') . "\n";
echo "Sekarang  : {$currentType}\n";

if (str_contains($currentType, "'Arsip'")) {
    echo "\nKolom sudah punya nilai 'Arsip'. Tak ada yang perlu diubah.\n";
    exit(0);
}

$newType = "enum('Tersedia','Terisi','Perbaikan','Arsip')";
echo "Menjadi   : {$newType}\n";

if (!$applyChanges) {
    echo "\nDry-run selesai. Jalankan ulang dengan --apply untuk benar-benar mengubah.\n";
    exit(0);
}

try {
    $db->execute("ALTER TABLE kamar MODIFY status {$newType} NULL DEFAULT 'Tersedia'");
} catch (\Throwable $throwable) {
    echo "\nGAGAL: " . $throwable->getMessage() . "\n";
    exit(1);
}

$after = $db->selectOne("SHOW COLUMNS FROM kamar WHERE Field = 'status'");
echo "\nBerhasil. Kolom sekarang: " . (string) ($after['Type'] ?? '?') . "\n";

$counts = $db->selectAll("SELECT status, COUNT(*) AS total FROM kamar GROUP BY status");
foreach ($counts as $row) {
    echo '  ' . str_pad((string) $row['status'], 12) . $row['total'] . " kamar\n";
}
