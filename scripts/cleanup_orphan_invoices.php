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

$revenueBefore = (float) ($db->selectOne(
    "SELECT COALESCE(SUM(nominal), 0) AS t FROM pembayaran WHERE status_verifikasi = 'Lunas'"
)['t'] ?? 0);

$orphans = $db->selectAll(
    "SELECT p.id_pembayaran, p.invoice_no, p.id_sewa, p.periode_mulai, p.nominal, s.status_sewa
     FROM pembayaran p
     JOIN sewa s ON s.id_sewa = p.id_sewa
     WHERE p.status_verifikasi = 'Menunggu'
     AND s.status_sewa IN ('Berhenti', 'Dibatalkan')
     AND (p.bukti_bayar IS NULL OR p.bukti_bayar = '')
     AND (p.metode_bayar IS NULL OR p.metode_bayar = '')
     ORDER BY p.id_pembayaran"
);

echo "=== Pembersihan invoice yatim ===\n";
echo 'Mode           : ' . ($applyChanges ? 'APPLY (menghapus)' : 'DRY-RUN (tidak mengubah apa pun)') . "\n";
echo 'Pendapatan awal: Rp ' . number_format($revenueBefore, 0, ',', '.') . "\n\n";

if ($orphans === []) {
    echo "Tidak ada invoice yatim. Tak ada yang perlu dibersihkan.\n";
    exit(0);
}

echo 'Ditemukan ' . count($orphans) . " invoice yatim:\n";
foreach ($orphans as $orphan) {
    echo sprintf(
        "  inv#%-4d %-18s sewa#%-3d (%s)  periode_mulai=%s  nominal=Rp %s\n",
        (int) $orphan['id_pembayaran'],
        (string) $orphan['invoice_no'],
        (int) $orphan['id_sewa'],
        (string) $orphan['status_sewa'],
        (string) ($orphan['periode_mulai'] ?? '-'),
        number_format((float) $orphan['nominal'], 0, ',', '.')
    );
}

if (!$applyChanges) {
    echo "\nDry-run selesai. Jalankan ulang dengan --apply untuk benar-benar menghapus.\n";
    exit(0);
}

$db->beginTransaction();

try {
    foreach ($orphans as $orphan) {
        $db->execute(
            "DELETE FROM pembayaran WHERE id_pembayaran = ?",
            [(int) $orphan['id_pembayaran']]
        );
    }

    $db->commit();
} catch (\Throwable $throwable) {
    $db->rollback();
    echo "\nGAGAL: " . $throwable->getMessage() . "\nTidak ada perubahan yang disimpan.\n";
    exit(1);
}

$revenueAfter = (float) ($db->selectOne(
    "SELECT COALESCE(SUM(nominal), 0) AS t FROM pembayaran WHERE status_verifikasi = 'Lunas'"
)['t'] ?? 0);

echo "\n" . count($orphans) . " invoice yatim dihapus.\n";
echo 'Pendapatan akhir: Rp ' . number_format($revenueAfter, 0, ',', '.')
    . ($revenueAfter === $revenueBefore ? "  (tidak berubah, sesuai harapan)\n" : "  (BERUBAH — periksa!)\n");
