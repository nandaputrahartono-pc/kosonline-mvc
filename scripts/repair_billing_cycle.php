<?php

declare(strict_types=1);

/**
 * Perbaiki kerusakan akibat bug "runaway Lunas".
 *
 * Gejala: admin bisa mengklik "Lunas" berulang untuk invoice periode yang BELUM terjadi.
 * Tiap klik memajukan `sewa.jatuh_tempo` +1 bulan dan melahirkan invoice masa depan baru,
 * sehingga jatuh tempo melompat bertahun-tahun dan Total Pendapatan menggelembung oleh
 * pembayaran fiktif.
 *
 * Yang dilakukan (per sewa aktif):
 *   1. Cari invoice terbayar yang SAH = status 'Lunas' DAN periodenya sudah benar-benar mulai
 *      (periode_mulai <= hari ini). Ini pembayaran nyata.
 *   2. Hapus invoice apa pun yang periodenya melewati akhir periode sah terakhir DAN belum
 *      ada bukti bayarnya (invoice fiktif hasil runaway + invoice masa depan yang nyangkut).
 *   3. Setel ulang `jatuh_tempo` = akhir periode sah terakhir + 1 hari.
 *
 * Invoice yang PUNYA bukti bayar tak pernah disentuh (itu pembayaran sungguhan).
 *
 * Pakai:
 *   php scripts/repair_billing_cycle.php           # dry-run: hanya melaporkan rencana
 *   php scripts/repair_billing_cycle.php --apply   # benar-benar memperbaiki
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
$today = date('Y-m-d');

$revenueBefore = (float) ($db->selectOne(
    "SELECT COALESCE(SUM(nominal), 0) AS t FROM pembayaran WHERE status_verifikasi = 'Lunas'"
)['t'] ?? 0);

$report = [];

foreach ($db->selectAll("SELECT id_sewa, tanggal_masuk, jatuh_tempo FROM sewa WHERE status_sewa = 'Aktif'") as $rental) {
    $rentalId = (int) $rental['id_sewa'];

    // 1) Pembayaran SAH terakhir: sudah lunas & periodenya memang sudah berjalan.
    $lastReal = $db->selectOne(
        "SELECT id_pembayaran, periode_mulai, periode_selesai
         FROM pembayaran
         WHERE id_sewa = ?
           AND status_verifikasi = 'Lunas'
           AND periode_mulai IS NOT NULL
           AND periode_mulai <= ?
         ORDER BY periode_mulai DESC
         LIMIT 1",
        [$rentalId, $today]
    );

    if ($lastReal === null) {
        continue; // belum ada pembayaran sah -> tak ada yang perlu dipulihkan
    }

    $lastEnd = (string) $lastReal['periode_selesai'];
    $correctDue = date('Y-m-d', strtotime($lastEnd . ' +1 day'));

    // 2) Invoice fiktif = periodenya melewati periode sah terakhir DAN tanpa bukti bayar.
    $bogus = $db->selectAll(
        "SELECT id_pembayaran, invoice_no, periode_mulai, status_verifikasi, nominal
         FROM pembayaran
         WHERE id_sewa = ?
           AND periode_mulai IS NOT NULL
           AND periode_mulai > ?
           AND (bukti_bayar IS NULL OR bukti_bayar = '')
         ORDER BY periode_mulai",
        [$rentalId, $lastEnd]
    );

    $currentDue = (string) $rental['jatuh_tempo'];
    $dueWrong = $currentDue !== $correctDue;

    if ($bogus === [] && !$dueWrong) {
        continue; // sewa ini sudah sehat
    }

    $fakeRevenue = 0.0;
    foreach ($bogus as $row) {
        if ((string) $row['status_verifikasi'] === 'Lunas') {
            $fakeRevenue += (float) $row['nominal'];
        }
    }

    $report[] = [
        'id_sewa' => $rentalId,
        'jatuh_tempo_sekarang' => $currentDue,
        'jatuh_tempo_benar' => $correctDue,
        'invoice_dihapus' => count($bogus),
        'pendapatan_fiktif_dibuang' => $fakeRevenue,
    ];

    if ($applyChanges) {
        foreach ($bogus as $row) {
            $db->execute("DELETE FROM pembayaran WHERE id_pembayaran = ?", [(int) $row['id_pembayaran']]);
        }

        $db->execute("UPDATE sewa SET jatuh_tempo = ? WHERE id_sewa = ?", [$correctDue, $rentalId]);
    }
}

$revenueAfter = $applyChanges
    ? (float) ($db->selectOne(
        "SELECT COALESCE(SUM(nominal), 0) AS t FROM pembayaran WHERE status_verifikasi = 'Lunas'"
    )['t'] ?? 0)
    : $revenueBefore - array_sum(array_column($report, 'pendapatan_fiktif_dibuang'));

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'sewa_diperbaiki' => $report,
    'pendapatan_sebelum' => $revenueBefore,
    'pendapatan_sesudah' => $revenueAfter,
    'catatan' => $report === [] ? 'Tidak ada yang perlu diperbaiki.' : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
