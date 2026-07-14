<?php

declare(strict_types=1);

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Skrip ini hanya boleh dijalankan lewat terminal (CLI).");
}

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$applyChanges = in_array('--apply', $argv, true);
$targetAvailableRooms = 24;

foreach ($argv as $argument) {
    if (str_starts_with($argument, '--target=')) {
        $targetAvailableRooms = max(10, (int) substr($argument, 9));
    }
}

$db = Database::getInstance();
$availableRow = $db->selectOne("SELECT COUNT(*) AS total FROM kamar WHERE status = 'Tersedia'");
$availableRooms = (int) ($availableRow['total'] ?? 0);
$roomsToCreate = max(0, $targetAvailableRooms - $availableRooms);
$createdRooms = [];

$kostRows = $db->selectAll('SELECT id_kost FROM kost ORDER BY id_kost ASC');
if ($kostRows === []) {
    echo json_encode([
        'mode' => $applyChanges ? 'applied' : 'dry-run',
        'error' => 'Tidak ada data kost untuk dijadikan cabang kamar demo.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}

$existingDemoRows = $db->selectAll("SELECT nomor_kamar FROM kamar WHERE nomor_kamar LIKE 'Demo P%'");
$existingDemoNumbers = [];
foreach ($existingDemoRows as $row) {
    $existingDemoNumbers[(string) $row['nomor_kamar']] = true;
}

$facilityOptions = [
    'AC, WIFI, Lemari',
    'WIFI, Kamar Mandi Luar, Meja',
    'AC, WIFI, Kasur',
    'WIFI, Lemari, Dapur Bersama',
];

if ($applyChanges && $roomsToCreate > 0) {
    $demoIndex = 1;
    for ($created = 0; $created < $roomsToCreate; $created++) {
        do {
            $roomNumber = sprintf('Demo P%02d', $demoIndex++);
        } while (isset($existingDemoNumbers[$roomNumber]));

        $branch = $kostRows[$created % count($kostRows)];
        $price = 330000 + (($created % 6) * 25000);
        $discount = match ($created % 5) {
            0 => 15,
            1 => 10,
            2 => 5,
            default => 0,
        };

        $db->insert(
            'INSERT INTO kamar (id_kost, nomor_kamar, lantai, fasilitas, deskripsi_kamar, harga, status, diskon_persen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $branch['id_kost'],
                $roomNumber,
                ($created % 2) + 1,
                $facilityOptions[$created % count($facilityOptions)],
                'Kamar demo untuk menguji pagination daftar kamar.',
                $price,
                'Tersedia',
                $discount,
            ]
        );

        $existingDemoNumbers[$roomNumber] = true;
        $createdRooms[] = $roomNumber;
    }
}

$finalRow = $db->selectOne("SELECT COUNT(*) AS total FROM kamar WHERE status = 'Tersedia'");

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'target_available_rooms' => $targetAvailableRooms,
    'available_before' => $availableRooms,
    'available_after' => (int) ($finalRow['total'] ?? $availableRooms),
    'created_count' => count($createdRooms),
    'created_rooms' => $createdRooms,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
