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

$columnType = static function (string $table, string $column) use ($db): ?string {
    foreach ($db->selectAll("DESCRIBE `{$table}`") as $definition) {
        if (strcasecmp((string) $definition['Field'], $column) === 0) {
            return (string) $definition['Type'];
        }
    }

    return null;
};

$hasTable = static function (string $table) use ($db): bool {
    return $db->selectOne(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$table]
    ) !== null;
};

$addColumn = static function (string $table, string $column, string $definition) use ($db, $hasColumn, $applyChanges, &$changes): void {
    if ($hasColumn($table, $column)) {
        return;
    }

    $changes[] = "{$table}.{$column}";

    if ($applyChanges) {
        $db->execute("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
    }
};

$statusType = $columnType('sewa', 'status_sewa') ?? '';
if (!str_contains($statusType, 'Menunggu Pembayaran') || !str_contains($statusType, 'Dibatalkan')) {
    $changes[] = 'sewa.status_sewa enum supports pending/cancelled';

    if ($applyChanges) {
        $db->execute(
            "ALTER TABLE `sewa`
             MODIFY `status_sewa` ENUM('Menunggu Pembayaran','Aktif','Berhenti','Dibatalkan')
             NULL DEFAULT 'Aktif'"
        );
    }
}

$addColumn('sewa', 'kode_booking', "`kode_booking` VARCHAR(30) NULL AFTER `id_kamar`");
$addColumn('sewa', 'dibuat_pada', "`dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status_sewa`");

$addColumn('pembayaran', 'invoice_no', "`invoice_no` VARCHAR(40) NULL AFTER `id_pembayaran`");
$addColumn('pembayaran', 'metode_bayar', "`metode_bayar` VARCHAR(40) NULL AFTER `bukti_bayar`");
$addColumn('pembayaran', 'periode_mulai', "`periode_mulai` DATE NULL AFTER `bulan_tagihan`");
$addColumn('pembayaran', 'periode_selesai', "`periode_selesai` DATE NULL AFTER `periode_mulai`");
$addColumn('pembayaran', 'harga_kamar', "`harga_kamar` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `nominal`");
$addColumn('pembayaran', 'diskon_kamar', "`diskon_kamar` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `harga_kamar`");
$addColumn('pembayaran', 'kode_promo', "`kode_promo` VARCHAR(30) NULL AFTER `diskon_kamar`");
$addColumn('pembayaran', 'diskon_promo', "`diskon_promo` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `kode_promo`");
$addColumn('pembayaran', 'biaya_admin', "`biaya_admin` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `diskon_promo`");
$addColumn('pembayaran', 'deposit', "`deposit` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `biaya_admin`");
$addColumn('pembayaran', 'total_bayar', "`total_bayar` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `deposit`");
$addColumn('pembayaran', 'nama_penyewa', "`nama_penyewa` VARCHAR(100) NULL AFTER `metode_bayar`");
$addColumn('pembayaran', 'email_penyewa', "`email_penyewa` VARCHAR(100) NULL AFTER `nama_penyewa`");
$addColumn('pembayaran', 'no_hp_penyewa', "`no_hp_penyewa` VARCHAR(20) NULL AFTER `email_penyewa`");
$addColumn('pembayaran', 'catatan', "`catatan` TEXT NULL AFTER `no_hp_penyewa`");
$addColumn('pembayaran', 'dibuat_pada', "`dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status_verifikasi`");

if ($hasColumn('pembayaran', 'invoice_no')) {
    $index = $db->selectOne(
        "SELECT INDEX_NAME
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'pembayaran'
         AND INDEX_NAME = 'idx_pembayaran_invoice_no'"
    );

    if ($index === null) {
        $changes[] = 'index:pembayaran.invoice_no';

        if ($applyChanges) {
            $db->execute('ALTER TABLE `pembayaran` ADD UNIQUE KEY `idx_pembayaran_invoice_no` (`invoice_no`)');
        }
    }
}

if (!$hasTable('promo_codes')) {
    $changes[] = 'table:promo_codes';

    if ($applyChanges) {
        $db->execute(
            "CREATE TABLE `promo_codes` (
                `id_promo` INT NOT NULL AUTO_INCREMENT,
                `kode` VARCHAR(30) NOT NULL,
                `nama_promo` VARCHAR(120) NOT NULL,
                `tipe_diskon` ENUM('persen','nominal') NOT NULL DEFAULT 'persen',
                `nilai_diskon` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `minimal_transaksi` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `kuota` INT NULL,
                `digunakan` INT NOT NULL DEFAULT 0,
                `mulai` DATE NULL,
                `selesai` DATE NULL,
                `aktif` TINYINT(1) NOT NULL DEFAULT 1,
                `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_promo`),
                UNIQUE KEY `idx_promo_codes_kode` (`kode`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}

if ($applyChanges && $hasTable('promo_codes')) {
    $seeds = [
        ['KOSHEMAT', 'Diskon 5% booking pertama', 'persen', 5, 300000, 100],
        ['KOSNEW50', 'Potongan Rp50.000 untuk penghuni baru', 'nominal', 50000, 500000, 50],
    ];

    foreach ($seeds as $seed) {
        $exists = $db->selectOne('SELECT id_promo FROM promo_codes WHERE kode = ?', [$seed[0]]);
        if ($exists !== null) {
            continue;
        }

        $db->insert(
            "INSERT INTO promo_codes (kode, nama_promo, tipe_diskon, nilai_diskon, minimal_transaksi, kuota, mulai, selesai, aktif)
             VALUES (?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1)",
            $seed
        );
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'changes' => array_values(array_unique($changes)),
], JSON_PRETTY_PRINT) . PHP_EOL;
