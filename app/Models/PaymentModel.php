<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PaymentModel extends Model
{
    public function unpaidCount(string $billingMonth): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total
             FROM sewa
             WHERE status_sewa = 'Aktif'
             AND id_sewa NOT IN (
                 SELECT id_sewa FROM pembayaran WHERE bulan_tagihan = ? AND status_verifikasi = 'Lunas'
             )",
            [$billingMonth]
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Pembayaran yang menunggu verifikasi admin (user sudah bayar/unggah bukti).
     * Tak perlu flag "dibaca": otomatis hilang begitu admin verifikasi/tolak.
     *
     * Hanya menghitung yang benar-benar bisa ditindak admin di tab Pembayaran:
     * sewa yang masih berjalan, dan usernya sudah bergerak (booking baru, unggah
     * bukti, atau memilih metode). Invoice bulanan hasil auto-generate yang belum
     * disentuh siapa pun adalah TAGIHAN, bukan permintaan verifikasi — itu sudah
     * punya notifikasi sendiri lewat RentalModel::countOverdue().
     */
    public function countPendingVerification(): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total
             FROM pembayaran p
             JOIN sewa s ON s.id_sewa = p.id_sewa
             WHERE p.status_verifikasi = 'Menunggu'
             AND s.status_sewa IN ('Menunggu Pembayaran', 'Aktif')
             AND (
                 s.status_sewa = 'Menunggu Pembayaran'
                 OR (p.bukti_bayar IS NOT NULL AND p.bukti_bayar <> '')
                 OR (p.metode_bayar IS NOT NULL AND p.metode_bayar <> '')
             )"
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Buang invoice terbuka yang murni dibuat sistem (ensureOpenInvoice) dan tak
     * pernah disentuh siapa pun. Dipakai saat sewa dihentikan/dibatalkan supaya
     * tak meninggalkan invoice yatim. Invoice yang sudah ada bukti/metode bayar
     * TIDAK diusik — itu jejak pembukuan.
     */
    public function voidOpenInvoices(int $rentalId): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total
             FROM pembayaran
             WHERE id_sewa = ?
             AND status_verifikasi = 'Menunggu'
             AND (bukti_bayar IS NULL OR bukti_bayar = '')
             AND (metode_bayar IS NULL OR metode_bayar = '')",
            [$rentalId]
        );
        $total = (int) ($row['total'] ?? 0);

        if ($total > 0) {
            $this->db->execute(
                "DELETE FROM pembayaran
                 WHERE id_sewa = ?
                 AND status_verifikasi = 'Menunggu'
                 AND (bukti_bayar IS NULL OR bukti_bayar = '')
                 AND (metode_bayar IS NULL OR metode_bayar = '')",
                [$rentalId]
            );
        }

        return $total;
    }

    public function totalPaidRevenue(): float
    {
        $row = $this->db->selectOne(
            "SELECT COALESCE(SUM(nominal), 0) AS total
             FROM pembayaran
             WHERE status_verifikasi = 'Lunas'"
        );

        return (float) ($row['total'] ?? 0);
    }

    public function paidRevenueForBillingMonth(string $billingMonth): float
    {
        $row = $this->db->selectOne(
            "SELECT COALESCE(SUM(nominal), 0) AS total
             FROM pembayaran
             WHERE bulan_tagihan = ?
             AND status_verifikasi = 'Lunas'",
            [$billingMonth]
        );

        return (float) ($row['total'] ?? 0);
    }

    public function monthlyRevenueTrend(int $months = 6): array
    {
        $months = max(1, min(12, $months));
        $start = new \DateTimeImmutable('first day of -' . ($months - 1) . ' months');
        $startDate = $start->format('Y-m-d');

        $rows = $this->db->selectAll(
            "SELECT DATE_FORMAT(tanggal_bayar, '%Y-%m') AS month_key,
                    COALESCE(SUM(nominal), 0) AS total
             FROM pembayaran
             WHERE status_verifikasi = 'Lunas'
             AND tanggal_bayar IS NOT NULL
             AND tanggal_bayar >= ?
             GROUP BY month_key
             ORDER BY month_key ASC",
            [$startDate]
        );

        $totalsByMonth = [];
        foreach ($rows as $row) {
            $totalsByMonth[(string) $row['month_key']] = (float) $row['total'];
        }

        $labels = [];
        $values = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->modify('+' . $i . ' months');
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $values[] = $totalsByMonth[$key] ?? 0.0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
            'max' => $values === [] ? 0.0 : max($values),
        ];
    }

    public function markPaid(int $rentalId, string $billingMonth, float $amount): void
    {
        $today = date('Y-m-d');
        $existing = $this->db->selectOne(
            "SELECT id_pembayaran FROM pembayaran WHERE id_sewa = ? AND bulan_tagihan = ?",
            [$rentalId, $billingMonth]
        );

        if ($existing !== null) {
            $this->db->execute(
                "UPDATE pembayaran
                 SET status_verifikasi = 'Lunas', tanggal_bayar = ?, nominal = ?
                 WHERE id_sewa = ? AND bulan_tagihan = ?",
                [$today, $amount, $rentalId, $billingMonth]
            );
            return;
        }

        $this->db->insert(
            "INSERT INTO pembayaran (id_sewa, bulan_tagihan, tanggal_bayar, nominal, status_verifikasi)
             VALUES (?, ?, ?, ?, 'Lunas')",
            [$rentalId, $billingMonth, $today, $amount]
        );
    }

    public function createPendingInvoice(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO pembayaran (
                id_sewa, invoice_no, bulan_tagihan, periode_mulai, periode_selesai,
                tanggal_bayar, nominal, harga_kamar, diskon_kamar, kode_promo,
                diskon_promo, biaya_admin, deposit, total_bayar, bukti_bayar,
                metode_bayar, nama_penyewa, email_penyewa, no_hp_penyewa,
                catatan, status_verifikasi
             ) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, 'Menunggu')",
            [
                $data['id_sewa'],
                $data['invoice_no'],
                $data['bulan_tagihan'],
                $data['periode_mulai'],
                $data['periode_selesai'],
                $data['total_bayar'],
                $data['harga_kamar'],
                $data['diskon_kamar'],
                $data['kode_promo'],
                $data['diskon_promo'],
                $data['biaya_admin'],
                $data['deposit'],
                $data['total_bayar'],
                $data['metode_bayar'],
                $data['nama_penyewa'],
                $data['email_penyewa'],
                $data['no_hp_penyewa'],
                $data['catatan'],
            ]
        );
    }

    public function findInvoiceById(int $paymentId): ?array
    {
        return $this->db->selectOne(
            "SELECT pembayaran.*, sewa.id_user, sewa.kode_booking, sewa.tanggal_masuk, sewa.jatuh_tempo, sewa.status_sewa,
                    kamar.id_kamar, kamar.nomor_kamar, kamar.lantai, kamar.fasilitas,
                    kost.nama_kost, kost.alamat, kost.foto_kost
             FROM pembayaran
             JOIN sewa ON pembayaran.id_sewa = sewa.id_sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE pembayaran.id_pembayaran = ?",
            [$paymentId]
        );
    }

    /**
     * Ambil invoice hanya jika benar milik user yang bersangkutan.
     * Dipakai untuk validasi sebelum user mengupload bukti transfer.
     */
    public function findInvoiceForUser(int $paymentId, int $userId): ?array
    {
        $invoice = $this->findInvoiceById($paymentId);

        if ($invoice === null || (int) ($invoice['id_user'] ?? 0) !== $userId) {
            return null;
        }

        return $invoice;
    }

    /**
     * Simpan nama file bukti transfer dan kembalikan status verifikasi ke "Menunggu"
     * supaya admin tahu ada bukti baru untuk dicek (termasuk saat sebelumnya Ditolak).
     */
    public function attachProof(int $paymentId, string $filename): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET bukti_bayar = ?, status_verifikasi = 'Menunggu'
             WHERE id_pembayaran = ?",
            [$filename, $paymentId]
        );
    }

    public function markPaidByRental(int $rentalId, float $amount): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET status_verifikasi = 'Lunas', tanggal_bayar = ?, nominal = ?, total_bayar = ?
             WHERE id_sewa = ?
             ORDER BY id_pembayaran DESC
             LIMIT 1",
            [date('Y-m-d'), $amount, $amount, $rentalId]
        );
    }

    public function rejectLatestByRental(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET status_verifikasi = 'Ditolak'
             WHERE id_sewa = ?
             ORDER BY id_pembayaran DESC
             LIMIT 1",
            [$rentalId]
        );
    }

    /**
     * Batalkan status lunas TANPA menghapus barisnya.
     *
     * Dulu memakai DELETE — itu memusnahkan invoice asli (invoice_no, bukti bayar,
     * promo, deposit) kalau bulan tagihannya kebetulan sama dengan bulan berjalan.
     */
    public function cancelPayment(int $rentalId, string $billingMonth): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET status_verifikasi = 'Menunggu', tanggal_bayar = NULL
             WHERE id_sewa = ? AND bulan_tagihan = ?",
            [$rentalId, $billingMonth]
        );
    }

    /**
     * Tandai satu invoice (berdasarkan id) sebagai lunas.
     * Basis verifikasi = INVOICE, bukan bulan kalender -> tak ada lagi baris stub/ganda.
     */
    public function markInvoicePaid(int $paymentId, float $amount): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET status_verifikasi = 'Lunas',
                 tanggal_bayar = ?,
                 nominal = ?,
                 total_bayar = IF(total_bayar > 0, total_bayar, ?)
             WHERE id_pembayaran = ?",
            [date('Y-m-d'), $amount, $amount, $paymentId]
        );
    }

    /**
     * Kembalikan invoice ke status menunggu (non-destruktif).
     */
    public function markInvoiceUnpaid(int $paymentId): void
    {
        $this->db->execute(
            "UPDATE pembayaran
             SET status_verifikasi = 'Menunggu', tanggal_bayar = NULL
             WHERE id_pembayaran = ?",
            [$paymentId]
        );
    }

    /**
     * Pastikan sewa AKTIF punya invoice terbuka untuk periode berjalan.
     *
     * Periode berjalan = [jatuh_tempo, jatuh_tempo + 1 bulan - 1 hari], jatuh temponya = jatuh_tempo.
     * IDEMPOTENT: kalau invoice untuk periode itu sudah ada, tak melakukan apa-apa.
     * Ini yang menutup siklus bulanan -> user punya invoice untuk dibayar tiap bulan.
     *
     * @return int|null id_pembayaran invoice yang baru dibuat, atau null kalau tak membuat apa pun.
     */
    public function ensureOpenInvoice(int $rentalId): ?int
    {
        $rental = $this->db->selectOne(
            "SELECT sewa.id_sewa, sewa.jatuh_tempo, sewa.status_sewa,
                    kamar.harga, kamar.diskon_persen,
                    users.nama_lengkap, users.email, users.no_hp
             FROM sewa
             JOIN kamar ON kamar.id_kamar = sewa.id_kamar
             LEFT JOIN users ON users.id_user = sewa.id_user
             WHERE sewa.id_sewa = ?",
            [$rentalId]
        );

        if ($rental === null || (string) $rental['status_sewa'] !== 'Aktif') {
            return null;
        }

        $dueDate = (string) ($rental['jatuh_tempo'] ?? '');
        if ($dueDate === '' || $dueDate === '0000-00-00') {
            return null;
        }

        // Sudah ada invoice untuk periode ini? -> jangan buat lagi (idempotent).
        $existing = $this->db->selectOne(
            "SELECT id_pembayaran FROM pembayaran WHERE id_sewa = ? AND periode_mulai = ?",
            [$rentalId, $dueDate]
        );

        if ($existing !== null) {
            return null;
        }

        $start = new \DateTimeImmutable($dueDate);
        $end = $start->modify('+1 month')->modify('-1 day');

        $price = (float) $rental['harga'];
        $discountPercent = (int) ($rental['diskon_persen'] ?? 0);
        $roomDiscount = round($price * $discountPercent / 100, 2);
        $total = max(0.0, $price - $roomDiscount);

        return $this->createPendingInvoice([
            'id_sewa' => $rentalId,
            'invoice_no' => 'INV-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'bulan_tagihan' => $start->format('F Y'),
            'periode_mulai' => $start->format('Y-m-d'),
            'periode_selesai' => $end->format('Y-m-d'),
            'harga_kamar' => $price,
            'diskon_kamar' => $roomDiscount,
            'kode_promo' => null,
            'diskon_promo' => 0,
            'biaya_admin' => 0,
            'deposit' => 0,
            'total_bayar' => $total,
            'metode_bayar' => null,
            'nama_penyewa' => $rental['nama_lengkap'] ?? null,
            'email_penyewa' => $rental['email'] ?? null,
            'no_hp_penyewa' => $rental['no_hp'] ?? null,
            'catatan' => 'Tagihan sewa bulanan. Silakan bayar sebelum jatuh tempo.',
        ]);
    }

    /**
     * Invoice terbaru milik sebuah sewa (invoice "berjalan").
     */
    public function latestInvoiceByRental(int $rentalId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM pembayaran WHERE id_sewa = ? ORDER BY id_pembayaran DESC LIMIT 1",
            [$rentalId]
        );
    }

    /**
     * Ambil invoice tertentu, dipastikan memang milik sewa yang bersangkutan.
     *
     * Admin menindak invoice yang DITAMPILKAN di tabel penagihan, bukan "invoice terakhir".
     * Keduanya bisa berbeda: sesudah pelunasan, ensureOpenInvoice melahirkan invoice periode
     * BERIKUTNYA yang id-nya lebih besar, sementara tabel tetap menampilkan invoice berjalan.
     * Kalau controller memakai yang terakhir, tombol "Batal" menyasar invoice yang salah.
     */
    public function findInvoiceForRental(int $invoiceId, int $rentalId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM pembayaran WHERE id_pembayaran = ? AND id_sewa = ?",
            [$invoiceId, $rentalId]
        );
    }

    /**
     * Bersihkan invoice BELUM lunas yang periodenya melewati tanggal tertentu.
     * Dipakai saat pelunasan dibatalkan, supaya invoice "masa depan" tak nyangkut.
     */
    public function deleteUnpaidInvoicesAfter(int $rentalId, string $date): void
    {
        $this->db->execute(
            "DELETE FROM pembayaran
             WHERE id_sewa = ?
               AND status_verifikasi <> 'Lunas'
               AND periode_mulai IS NOT NULL
               AND periode_mulai > ?",
            [$rentalId, $date]
        );
    }

    public function getHistoryByRentalId(int $rentalId): array
    {
        return $this->db->selectAll(
            "SELECT * FROM pembayaran WHERE id_sewa = ? ORDER BY id_pembayaran DESC",
            [$rentalId]
        );
    }

    public function getHistoryByUserId(int $userId): array
    {
        return $this->db->selectAll(
            "SELECT pembayaran.*, sewa.kode_booking, sewa.status_sewa,
                    kamar.nomor_kamar, kamar.lantai,
                    kost.nama_kost
             FROM pembayaran
             JOIN sewa ON pembayaran.id_sewa = sewa.id_sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE sewa.id_user = ?
             ORDER BY pembayaran.id_pembayaran DESC",
            [$userId]
        );
    }

    public function deleteByRoomId(int $roomId): void
    {
        $this->db->execute(
            "DELETE FROM pembayaran WHERE id_sewa IN (SELECT id_sewa FROM sewa WHERE id_kamar = ?)",
            [$roomId]
        );
    }

    public function deleteByRentalId(int $rentalId): void
    {
        $this->db->execute("DELETE FROM pembayaran WHERE id_sewa = ?", [$rentalId]);
    }
}
