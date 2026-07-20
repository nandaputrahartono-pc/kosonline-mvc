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

$broken = $db->selectAll(
    "SELECT id_sewa, status_sewa, tanggal_masuk, jatuh_tempo,
            DATE_ADD(tanggal_masuk, INTERVAL 1 MONTH) AS jatuh_tempo_benar
     FROM sewa
     WHERE jatuh_tempo < tanggal_masuk
     ORDER BY id_sewa"
);

echo "=== Perbaikan jatuh tempo mustahil ===\n";
echo 'Mode : ' . ($applyChanges ? 'APPLY (memperbaiki)' : 'DRY-RUN (tidak mengubah apa pun)') . "\n\n";

if ($broken === []) {
    echo "Tidak ada sewa dengan jatuh tempo mustahil. Semua sehat.\n";
    exit(0);
}

echo 'Ditemukan ' . count($broken) . " sewa bermasalah:\n";
foreach ($broken as $row) {
    echo sprintf(
        "  sewa#%-3d %-20s masuk=%s  jatuh_tempo=%s  ->  %s\n",
        (int) $row['id_sewa'],
        (string) $row['status_sewa'],
        (string) $row['tanggal_masuk'],
        (string) $row['jatuh_tempo'],
        (string) $row['jatuh_tempo_benar']
    );
}

if (!$applyChanges) {
    echo "\nDry-run selesai. Jalankan ulang dengan --apply untuk benar-benar memperbaiki.\n";
    exit(0);
}

$db->beginTransaction();

try {
    foreach ($broken as $row) {
        $db->execute(
            "UPDATE sewa SET jatuh_tempo = ? WHERE id_sewa = ?",
            [(string) $row['jatuh_tempo_benar'], (int) $row['id_sewa']]
        );
    }

    $db->commit();
} catch (\Throwable $throwable) {
    $db->rollback();
    echo "\nGAGAL: " . $throwable->getMessage() . "\nTidak ada perubahan yang disimpan.\n";
    exit(1);
}

$sisa = (int) ($db->selectOne("SELECT COUNT(*) AS total FROM sewa WHERE jatuh_tempo < tanggal_masuk")['total'] ?? 0);

echo "\n" . count($broken) . " sewa diperbaiki.\n";
echo "Sisa yang masih bermasalah: {$sisa}" . ($sisa === 0 ? "  (bersih)\n" : "  (PERIKSA!)\n");
