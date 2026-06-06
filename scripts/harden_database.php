<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$applyChanges = in_array('--apply', $argv, true);
$db = Database::getInstance();
$passwordUpdates = [
    'users' => 0,
    'admin' => 0,
];
$dueDateUpdates = 0;
$transactionStarted = false;

try {
    if ($applyChanges) {
        $db->beginTransaction();
        $transactionStarted = true;
    }

    foreach ([
        'users' => 'id_user',
        'admin' => 'id_admin',
    ] as $table => $primaryKey) {
        $rows = $db->selectAll("SELECT {$primaryKey}, password FROM {$table}");

        foreach ($rows as $row) {
            $storedPassword = (string) $row['password'];
            if (password_get_info($storedPassword)['algo'] !== null) {
                continue;
            }

            $passwordUpdates[$table]++;

            if ($applyChanges) {
                $db->execute(
                    "UPDATE {$table} SET password = ? WHERE {$primaryKey} = ?",
                    [password_hash($storedPassword, PASSWORD_DEFAULT), $row[$primaryKey]]
                );
            }
        }
    }

    $rentals = $db->selectAll(
        "SELECT id_sewa, tanggal_masuk
         FROM sewa
         WHERE tanggal_masuk IS NOT NULL
         AND YEAR(jatuh_tempo) = 0"
    );

    foreach ($rentals as $rental) {
        $dueDateUpdates++;

        if ($applyChanges) {
            $dueDate = date('Y-m-d', strtotime((string) $rental['tanggal_masuk'] . ' +1 month'));
            $db->execute("UPDATE sewa SET jatuh_tempo = ? WHERE id_sewa = ?", [$dueDate, $rental['id_sewa']]);
        }
    }

    if ($applyChanges) {
        $db->commit();
    }
} catch (Throwable $throwable) {
    if ($transactionStarted) {
        $db->rollback();
    }

    fwrite(STDERR, 'Migrasi gagal: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'passwords_to_hash' => $passwordUpdates,
    'due_dates_to_repair' => $dueDateUpdates,
], JSON_PRETTY_PRINT) . PHP_EOL;
