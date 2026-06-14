<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ChatModel extends Model
{
    public function getOrCreateThread(int $userId, ?int $roomId = null, ?string $subject = null): int
    {
        $existing = $this->db->selectOne(
            "SELECT id_thread FROM chat_threads WHERE id_user = ? AND status = 'open' ORDER BY id_thread ASC LIMIT 1",
            [$userId]
        );

        if ($existing !== null) {
            return (int) $existing['id_thread'];
        }

        return $this->db->insert(
            "INSERT INTO chat_threads (id_user, id_kamar, subjek) VALUES (?, ?, ?)",
            [$userId, null, $subject ?: 'Chat dengan Admin']
        );
    }

    public function addMessage(int $threadId, string $senderType, string $message, ?int $roomId = null): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }

        $this->db->insert(
            "INSERT INTO chat_messages (id_thread, sender_type, tipe_pesan, id_kamar, isi_pesan) VALUES (?, ?, 'text', ?, ?)",
            [$threadId, $senderType, $roomId !== null && $roomId > 0 ? $roomId : null, $message]
        );

        $this->db->execute("UPDATE chat_threads SET diupdate_pada = CURRENT_TIMESTAMP WHERE id_thread = ?", [$threadId]);
    }

    public function addRoomCardMessage(int $threadId, int $roomId): void
    {
        if ($roomId <= 0) {
            return;
        }

        $latest = $this->db->selectOne(
            "SELECT tipe_pesan, id_kamar
             FROM chat_messages
             WHERE id_thread = ?
             ORDER BY dibuat_pada DESC, id_message DESC
             LIMIT 1",
            [$threadId]
        );

        if (
            $latest !== null
            && ($latest['tipe_pesan'] ?? '') === 'room_card'
            && (int) ($latest['id_kamar'] ?? 0) === $roomId
        ) {
            return;
        }

        $this->db->insert(
            "INSERT INTO chat_messages (id_thread, sender_type, tipe_pesan, id_kamar, isi_pesan)
             VALUES (?, 'user', 'room_card', ?, '')",
            [$threadId, $roomId]
        );

        $this->db->execute("UPDATE chat_threads SET diupdate_pada = CURRENT_TIMESTAMP WHERE id_thread = ?", [$threadId]);
    }

    public function getThreadsByUserId(int $userId): array
    {
        return $this->db->selectAll(
            "SELECT chat_threads.*,
                    CASE
                        WHEN latest.tipe_pesan = 'room_card' THEN CONCAT('Mengirim detail ', COALESCE(latest_kost.nama_kost, 'kamar'))
                        ELSE latest.isi_pesan
                    END AS pesan_terakhir,
                    latest.sender_type AS pengirim_terakhir,
                    latest.tipe_pesan AS tipe_pesan_terakhir
             FROM chat_threads
             LEFT JOIN chat_messages latest ON latest.id_message = (
                SELECT cm.id_message FROM chat_messages cm
                WHERE cm.id_thread = chat_threads.id_thread
                ORDER BY cm.dibuat_pada DESC, cm.id_message DESC LIMIT 1
             )
             LEFT JOIN kamar latest_kamar ON latest.id_kamar = latest_kamar.id_kamar
             LEFT JOIN kost latest_kost ON latest_kamar.id_kost = latest_kost.id_kost
             WHERE chat_threads.id_user = ?
             ORDER BY chat_threads.diupdate_pada DESC",
            [$userId]
        );
    }

    public function getAllThreads(): array
    {
        return $this->db->selectAll(
            "SELECT chat_threads.*, users.nama_lengkap, users.email, users.no_hp,
                    CASE
                        WHEN latest.tipe_pesan = 'room_card' THEN CONCAT('Mengirim detail ', COALESCE(latest_kost.nama_kost, 'kamar'))
                        ELSE latest.isi_pesan
                    END AS pesan_terakhir,
                    latest.sender_type AS pengirim_terakhir,
                    latest.tipe_pesan AS tipe_pesan_terakhir
             FROM chat_threads
             JOIN users ON chat_threads.id_user = users.id_user
             LEFT JOIN chat_messages latest ON latest.id_message = (
                SELECT cm.id_message FROM chat_messages cm
                WHERE cm.id_thread = chat_threads.id_thread
                ORDER BY cm.dibuat_pada DESC, cm.id_message DESC LIMIT 1
             )
             LEFT JOIN kamar latest_kamar ON latest.id_kamar = latest_kamar.id_kamar
             LEFT JOIN kost latest_kost ON latest_kamar.id_kost = latest_kost.id_kost
             ORDER BY chat_threads.diupdate_pada DESC"
        );
    }

    public function getThreadForUser(int $threadId, int $userId): ?array
    {
        return $this->db->selectOne(
            "SELECT chat_threads.*
             FROM chat_threads
             WHERE chat_threads.id_thread = ? AND chat_threads.id_user = ?",
            [$threadId, $userId]
        );
    }

    public function getThreadForAdmin(int $threadId): ?array
    {
        return $this->db->selectOne(
            "SELECT chat_threads.*, users.nama_lengkap, users.email, users.no_hp
             FROM chat_threads
             JOIN users ON chat_threads.id_user = users.id_user
             WHERE chat_threads.id_thread = ?",
            [$threadId]
        );
    }

    public function getMessages(int $threadId): array
    {
        return $this->db->selectAll(
            "SELECT chat_messages.*, kamar.nomor_kamar, kamar.harga, kamar.status AS status_kamar,
                    kost.nama_kost, kost.foto_kost, kost.alamat
             FROM chat_messages
             LEFT JOIN kamar ON chat_messages.id_kamar = kamar.id_kamar
             LEFT JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE chat_messages.id_thread = ?
             ORDER BY chat_messages.dibuat_pada ASC, chat_messages.id_message ASC",
            [$threadId]
        );
    }

    public function setTyping(int $threadId, string $actorType, bool $isTyping): void
    {
        $this->db->execute(
            "INSERT INTO chat_typing (id_thread, actor_type, is_typing)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE is_typing = VALUES(is_typing), updated_at = CURRENT_TIMESTAMP",
            [$threadId, $actorType, $isTyping ? 1 : 0]
        );
    }

    public function isTyping(int $threadId, string $actorType): bool
    {
        $row = $this->db->selectOne(
            "SELECT is_typing
             FROM chat_typing
             WHERE id_thread = ?
             AND actor_type = ?
             AND is_typing = 1
             AND updated_at >= DATE_SUB(NOW(), INTERVAL 6 SECOND)",
            [$threadId, $actorType]
        );

        return $row !== null;
    }

    public function formatMessagesForJson(array $messages): array
    {
        return array_map(function (array $message): array {
            $type = (string) ($message['tipe_pesan'] ?? 'text');

            return [
                'id_message' => (int) $message['id_message'],
                'sender_type' => (string) $message['sender_type'],
                'tipe_pesan' => $type,
                'isi_pesan' => (string) $message['isi_pesan'],
                'dibuat_pada' => (string) $message['dibuat_pada'],
                'waktu_label' => date('d M Y H:i', strtotime((string) $message['dibuat_pada'])),
                'room_card' => !empty($message['id_kamar']) ? $this->roomCardFromRow($message) : null,
            ];
        }, $messages);
    }

    public function roomContextForJson(?array $thread): ?array
    {
        if ($thread === null || empty($thread['id_kamar'])) {
            return null;
        }

        return [
            'id_kamar' => (int) $thread['id_kamar'],
            'title' => (string) (($thread['nama_kost'] ?? 'KosOnline') . ' - ' . $this->formatRoomLabel($thread['nomor_kamar'] ?? null)),
            'subtitle' => (string) ($thread['alamat'] ?? ''),
            'harga' => (float) ($thread['harga'] ?? 0),
            'status' => (string) ($thread['status_kamar'] ?? '-'),
            'foto_kost' => (string) ($thread['foto_kost'] ?? ''),
            'detail_url' => url('/rooms/detail?id=' . (int) $thread['id_kamar']),
            'image_url' => upload_asset((string) ($thread['foto_kost'] ?? '')),
        ];
    }

    public function roomCardFromRow(array $row): ?array
    {
        if (empty($row['id_kamar'])) {
            return null;
        }

        return [
            'id_kamar' => (int) $row['id_kamar'],
            'title' => (string) (($row['nama_kost'] ?? 'KosOnline') . ' - ' . $this->formatRoomLabel($row['nomor_kamar'] ?? null)),
            'subtitle' => (string) ($row['alamat'] ?? ''),
            'harga' => (float) ($row['harga'] ?? 0),
            'status' => (string) ($row['status_kamar'] ?? '-'),
            'detail_url' => url('/rooms/detail?id=' . (int) $row['id_kamar']),
            'image_url' => upload_asset((string) ($row['foto_kost'] ?? '')),
        ];
    }

    public function roomCardByRoomId(int $roomId): ?array
    {
        if ($roomId <= 0) {
            return null;
        }

        $row = $this->db->selectOne(
            "SELECT kamar.id_kamar, kamar.nomor_kamar, kamar.harga, kamar.status AS status_kamar,
                    kost.nama_kost, kost.foto_kost, kost.alamat
             FROM kamar
             JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE kamar.id_kamar = ?",
            [$roomId]
        );

        return $row !== null ? $this->roomCardFromRow($row) : null;
    }

    private function formatRoomLabel(mixed $roomNumber): string
    {
        $label = trim((string) $roomNumber);
        if ($label === '') {
            return 'Kamar -';
        }

        return preg_match('/^kamar\b/i', $label) === 1 ? $label : 'Kamar ' . $label;
    }
}
