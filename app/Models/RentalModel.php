<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RentalModel extends Model
{
    public function countActive(): int
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS total FROM sewa WHERE status_sewa = 'Aktif'");

        return (int) ($row['total'] ?? 0);
    }

    public function getActiveByUserId(int $userId): ?array
    {
        return $this->db->selectOne(
            "SELECT sewa.*, kamar.nomor_kamar, kamar.harga, kamar.lantai, kamar.fasilitas, kost.nama_kost, kost.alamat, kost.foto_kost
             FROM sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE sewa.id_user = ? AND sewa.status_sewa = 'Aktif'",
            [$userId]
        );
    }

    public function getDashboardRowsByUserId(int $userId): array
    {
        return $this->db->selectAll(
            "SELECT sewa.*, kamar.nomor_kamar, kamar.harga, kamar.lantai, kamar.fasilitas,
                    kost.nama_kost, kost.alamat, kost.foto_kost,
                    pembayaran.id_pembayaran, pembayaran.invoice_no, pembayaran.total_bayar,
                    pembayaran.status_verifikasi, pembayaran.metode_bayar,
                    pembayaran.periode_mulai, pembayaran.periode_selesai
             FROM sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             LEFT JOIN pembayaran ON pembayaran.id_pembayaran = (
                SELECT p.id_pembayaran
                FROM pembayaran p
                WHERE p.id_sewa = sewa.id_sewa
                ORDER BY p.id_pembayaran DESC
                LIMIT 1
             )
             WHERE sewa.id_user = ?
               AND sewa.disembunyikan = 0
             ORDER BY sewa.id_sewa DESC",
            [$userId]
        );
    }

    /**
     * Sembunyikan sewa dari daftar user (bukan hapus!).
     *
     * Catatan pembayaran sengaja TIDAK disentuh supaya Total Pendapatan admin & riwayat
     * keuangan tetap utuh — user tak bisa "menghapus" uang yang sudah masuk.
     */
    public function hideForUser(int $rentalId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sewa SET disembunyikan = 1 WHERE id_sewa = ? AND id_user = ?",
            [$rentalId, $userId]
        );
    }

    public function getActiveTenantByRoomId(int $roomId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM sewa WHERE id_kamar = ? AND status_sewa = 'Aktif'",
            [$roomId]
        );
    }

    public function getActiveByUserIdOnly(int $userId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM sewa WHERE id_user = ? AND status_sewa = 'Aktif'",
            [$userId]
        );
    }

    public function createActive(int $userId, int $roomId): int
    {
        $moveInDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime($moveInDate . ' +1 month'));

        return $this->db->insert(
            "INSERT INTO sewa (id_user, id_kamar, tanggal_masuk, jatuh_tempo, status_sewa)
             VALUES (?, ?, ?, ?, 'Aktif')",
            [$userId, $roomId, $moveInDate, $dueDate]
        );
    }

    public function createPending(int $userId, int $roomId, string $moveInDate, string $dueDate, string $bookingCode): int
    {
        return $this->db->insert(
            "INSERT INTO sewa (id_user, id_kamar, kode_booking, tanggal_masuk, jatuh_tempo, status_sewa)
             VALUES (?, ?, ?, ?, ?, 'Menunggu Pembayaran')",
            [$userId, $roomId, $bookingCode, $moveInDate, $dueDate]
        );
    }

    public function findActiveById(int $rentalId): ?array
    {
        return $this->db->selectOne(
            "SELECT sewa.*, kamar.harga
             FROM sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             WHERE sewa.id_sewa = ? AND sewa.status_sewa = 'Aktif'",
            [$rentalId]
        );
    }

    public function findByIdWithRoom(int $rentalId): ?array
    {
        return $this->db->selectOne(
            "SELECT sewa.*, kamar.harga, kamar.id_kamar
             FROM sewa
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             WHERE sewa.id_sewa = ?",
            [$rentalId]
        );
    }

    public function activate(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE sewa SET status_sewa = 'Aktif' WHERE id_sewa = ?",
            [$rentalId]
        );
    }

    /**
     * Majukan jatuh tempo satu bulan (dipanggil saat pembayaran bulanan diverifikasi lunas).
     */
    public function advanceDueDate(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE sewa SET jatuh_tempo = DATE_ADD(jatuh_tempo, INTERVAL 1 MONTH) WHERE id_sewa = ?",
            [$rentalId]
        );
    }

    /**
     * Mundurkan jatuh tempo satu bulan (membatalkan efek advanceDueDate saat status lunas dibatalkan).
     *
     * Jaring pengaman: jatuh tempo TIDAK PERNAH boleh mundur melewati tanggal masuk. Siklus
     * pertama sudah dimulai sejak tanggal masuk, jadi jatuh tempo paling awal adalah
     * tanggal_masuk + 1 bulan. Tanpa syarat ini, "Batal" yang terpanggil lebih dari sekali
     * bisa menyeret jatuh tempo ke SEBELUM penyewa masuk.
     */
    public function retreatDueDate(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE sewa
             SET jatuh_tempo = DATE_SUB(jatuh_tempo, INTERVAL 1 MONTH)
             WHERE id_sewa = ?
             AND DATE_SUB(jatuh_tempo, INTERVAL 1 MONTH) > tanggal_masuk",
            [$rentalId]
        );
    }

    /**
     * Kembalikan sewa ke status 'Menunggu Pembayaran' — kebalikan dari activate().
     * Dipakai saat admin membatalkan pelunasan invoice BOOKING (invoice pertama).
     */
    public function revertToPending(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE sewa SET status_sewa = 'Menunggu Pembayaran' WHERE id_sewa = ?",
            [$rentalId]
        );
    }

    public function cancelPending(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE sewa SET status_sewa = 'Dibatalkan', tanggal_keluar = ? WHERE id_sewa = ?",
            [date('Y-m-d'), $rentalId]
        );
    }

    public function stopActiveByRentalId(int $rentalId): void
    {
        $this->db->execute(
            "UPDATE sewa SET status_sewa = 'Berhenti', tanggal_keluar = ? WHERE id_sewa = ?",
            [date('Y-m-d'), $rentalId]
        );
    }

    public function stopActiveByRoomId(int $roomId): void
    {
        $this->db->execute(
            "UPDATE sewa SET status_sewa = 'Berhenti', tanggal_keluar = ? WHERE id_kamar = ? AND status_sewa = 'Aktif'",
            [date('Y-m-d'), $roomId]
        );
    }

    public function deleteByRoomId(int $roomId): void
    {
        $this->db->execute("DELETE FROM sewa WHERE id_kamar = ?", [$roomId]);
    }

    public function findByIdForUser(int $rentalId, int $userId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM sewa WHERE id_sewa = ? AND id_user = ?",
            [$rentalId, $userId]
        );
    }

    public function deleteById(int $rentalId): void
    {
        $this->db->execute("DELETE FROM sewa WHERE id_sewa = ?", [$rentalId]);
    }

    public function getCancelledBookings(): array
    {
        return $this->db->selectAll(
            "SELECT sewa.id_sewa, sewa.kode_booking, sewa.tanggal_masuk, sewa.tanggal_keluar,
                    users.nama_lengkap, users.no_hp,
                    kost.nama_kost, kamar.nomor_kamar,
                    pembayaran.invoice_no, pembayaran.total_bayar
             FROM sewa
             LEFT JOIN users ON sewa.id_user = users.id_user
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             LEFT JOIN pembayaran ON pembayaran.id_pembayaran = (
                SELECT p.id_pembayaran FROM pembayaran p
                WHERE p.id_sewa = sewa.id_sewa
                ORDER BY p.id_pembayaran DESC LIMIT 1
             )
             WHERE sewa.status_sewa = 'Dibatalkan'
             ORDER BY sewa.id_sewa DESC"
        );
    }

    /**
     * Baris tagihan admin = sewa aktif/menunggu + INVOICE BERJALAN-nya (invoice terbaru).
     *
     * Dulu invoice dijodohkan lewat `bulan_tagihan` (bulan kalender), padahal periode sewa
     * memakai siklus tanggal masuk (mis. tgl 21 -> 20). Keduanya melenceng sehingga verifikasi
     * bikin baris ganda/stub. Sekarang cukup ambil invoice terbaru milik sewa itu.
     */
    public function getAdminBillingRows(): array
    {
        return $this->db->selectAll(
            "SELECT
                sewa.id_sewa,
                COALESCE(pembayaran.nama_penyewa, users.nama_lengkap) AS nama_lengkap,
                COALESCE(pembayaran.no_hp_penyewa, users.no_hp) AS no_hp,
                kost.nama_kost,
                kamar.nomor_kamar,
                kamar.harga,
                pembayaran.status_verifikasi,
                sewa.status_sewa,
                sewa.jatuh_tempo,
                pembayaran.id_pembayaran,
                pembayaran.invoice_no,
                pembayaran.total_bayar,
                pembayaran.metode_bayar,
                pembayaran.periode_mulai,
                pembayaran.periode_selesai,
                pembayaran.nama_penyewa,
                pembayaran.email_penyewa,
                pembayaran.no_hp_penyewa,
                pembayaran.bukti_bayar
             FROM sewa
             LEFT JOIN users ON sewa.id_user = users.id_user
             JOIN kamar ON sewa.id_kamar = kamar.id_kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             LEFT JOIN pembayaran ON pembayaran.id_pembayaran = (
                SELECT p.id_pembayaran
                FROM pembayaran p
                WHERE p.id_sewa = sewa.id_sewa
                  AND (
                        -- booking awal: selalu tampilkan invoice booking-nya
                        sewa.status_sewa = 'Menunggu Pembayaran'
                        -- invoice yang SUDAH lunas selalu boleh dipilih (cuma memunculkan
                        -- tombol Batal, bukan Lunas). Ini penting untuk booking dengan tanggal
                        -- masuk di masa depan (mis. Bayar Saat Survei yang dibayar di muka):
                        -- periodenya belum mulai, tapi barisnya tak boleh kosong.
                        OR p.status_verifikasi = 'Lunas'
                        -- selain itu: hanya invoice yang SUDAH waktunya diurus, yaitu
                        -- periodenya sudah mulai ATAU penyewa sudah mengunggah bukti.
                        -- Invoice periode masa depan yang BELUM lunas sengaja TIDAK dipilih
                        -- supaya admin tak bisa melunasinya berulang (bug runaway).
                        OR p.periode_mulai IS NULL
                        OR p.periode_mulai <= CURDATE()
                        OR p.bukti_bayar IS NOT NULL
                  )
                ORDER BY
                    -- 1) yang BELUM lunas didahulukan (itu yang butuh aksi admin)
                    (p.status_verifikasi = 'Lunas') ASC,
                    -- 2) di antara yang belum lunas: yang paling lama (paling mendesak)
                    CASE WHEN p.status_verifikasi <> 'Lunas' THEN p.periode_mulai END ASC,
                    -- 3) kalau semua sudah lunas: ambil periode TERBARU (periode berjalan)
                    CASE WHEN p.status_verifikasi = 'Lunas' THEN p.periode_mulai END DESC,
                    p.id_pembayaran DESC
                LIMIT 1
             )
             WHERE sewa.status_sewa IN ('Menunggu Pembayaran', 'Aktif')
             ORDER BY sewa.id_sewa DESC"
        );
    }

    /**
     * Sewa aktif yang lewat jatuh tempo DAN invoice berjalannya belum lunas (menunggak).
     * Status turunan: dihitung, tidak disimpan di kolom mana pun.
     */
    public function countOverdue(): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total
             FROM sewa
             WHERE sewa.status_sewa = 'Aktif'
               AND sewa.jatuh_tempo < CURDATE()
               AND COALESCE((
                    SELECT p.status_verifikasi
                    FROM pembayaran p
                    WHERE p.id_sewa = sewa.id_sewa
                    ORDER BY p.id_pembayaran DESC
                    LIMIT 1
               ), 'Menunggu') <> 'Lunas'"
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Semua sewa aktif (untuk ensureOpenInvoice saat dashboard dimuat).
     *
     * @return list<int>
     */
    public function activeRentalIds(): array
    {
        $rows = $this->db->selectAll("SELECT id_sewa FROM sewa WHERE status_sewa = 'Aktif'");

        return array_map(static fn (array $row): int => (int) $row['id_sewa'], $rows);
    }
}
