<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ChatModel extends Model
{
    public function getOrCreateThread(int $userId, ?int $roomId = null, ?string $subject = null): int
    {
        if ($roomId !== null && $roomId > 0) {
            $existing = $this->db->selectOne(
                "SELECT id_thread FROM chat_threads WHERE id_user = ? AND id_kamar = ? AND status = 'open' ORDER BY id_thread DESC LIMIT 1",
                [$userId, $roomId]
            );
        } else {
            $existing = $this->db->selectOne(
                "SELECT id_thread FROM chat_threads WHERE id_user = ? AND id_kamar IS NULL AND status = 'open' ORDER BY id_thread DESC LIMIT 1",
                [$userId]
            );
        }

        if ($existing !== null) {
            return (int) $existing['id_thread'];
        }

        return $this->db->insert(
            "INSERT INTO chat_threads (id_user, id_kamar, subjek) VALUES (?, ?, ?)",
            [$userId, $roomId, $subject ?: 'Chat dengan Admin']
        );
    }

    public function addMessage(int $threadId, string $senderType, string $message): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }

        $this->db->insert(
            "INSERT INTO chat_messages (id_thread, sender_type, isi_pesan) VALUES (?, ?, ?)",
            [$threadId, $senderType, $message]
        );

        $this->db->execute("UPDATE chat_threads SET diupdate_pada = CURRENT_TIMESTAMP WHERE id_thread = ?", [$threadId]);
    }

    public function getThreadsByUserId(int $userId): array
    {
        return $this->db->selectAll(
            "SELECT chat_threads.*, kamar.nomor_kamar, kamar.harga, kamar.status AS status_kamar,
                    kost.nama_kost, kost.foto_kost, kost.alamat,
                    latest.isi_pesan AS pesan_terakhir, latest.sender_type AS pengirim_terakhir
             FROM chat_threads
             LEFT JOIN kamar ON chat_threads.id_kamar = kamar.id_kamar
             LEFT JOIN kost ON kamar.id_kost = kost.id_kost
             LEFT JOIN chat_messages latest ON latest.id_message = (
                SELECT cm.id_message FROM chat_messages cm
                WHERE cm.id_thread = chat_threads.id_thread
                ORDER BY cm.id_message DESC LIMIT 1
             )
             WHERE chat_threads.id_user = ?
             ORDER BY chat_threads.diupdate_pada DESC",
            [$userId]
        );
    }

    public function getAllThreads(): array
    {
        return $this->db->selectAll(
            "SELECT chat_threads.*, users.nama_lengkap, users.email, users.no_hp,
                    kamar.nomor_kamar, kamar.harga, kamar.status AS status_kamar,
                    kost.nama_kost, kost.foto_kost, kost.alamat,
                    latest.isi_pesan AS pesan_terakhir, latest.sender_type AS pengirim_terakhir
             FROM chat_threads
             JOIN users ON chat_threads.id_user = users.id_user
             LEFT JOIN kamar ON chat_threads.id_kamar = kamar.id_kamar
             LEFT JOIN kost ON kamar.id_kost = kost.id_kost
             LEFT JOIN chat_messages latest ON latest.id_message = (
                SELECT cm.id_message FROM chat_messages cm
                WHERE cm.id_thread = chat_threads.id_thread
                ORDER BY cm.id_message DESC LIMIT 1
             )
             ORDER BY chat_threads.diupdate_pada DESC"
        );
    }

    public function getThreadForUser(int $threadId, int $userId): ?array
    {
        return $this->db->selectOne(
            "SELECT chat_threads.*, kamar.nomor_kamar, kamar.harga, kamar.status AS status_kamar,
                    kost.nama_kost, kost.foto_kost, kost.alamat
             FROM chat_threads
             LEFT JOIN kamar ON chat_threads.id_kamar = kamar.id_kamar
             LEFT JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE chat_threads.id_thread = ? AND chat_threads.id_user = ?",
            [$threadId, $userId]
        );
    }

    public function getThreadForAdmin(int $threadId): ?array
    {
        return $this->db->selectOne(
            "SELECT chat_threads.*, users.nama_lengkap, users.email, users.no_hp,
                    kamar.nomor_kamar, kamar.harga, kamar.status AS status_kamar,
                    kost.nama_kost, kost.foto_kost, kost.alamat
             FROM chat_threads
             JOIN users ON chat_threads.id_user = users.id_user
             LEFT JOIN kamar ON chat_threads.id_kamar = kamar.id_kamar
             LEFT JOIN kost ON kamar.id_kost = kost.id_kost
             WHERE chat_threads.id_thread = ?",
            [$threadId]
        );
    }

    public function getMessages(int $threadId): array
    {
        return $this->db->selectAll(
            "SELECT * FROM chat_messages WHERE id_thread = ? ORDER BY id_message ASC",
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
        return array_map(static fn(array $message): array => [
            'id_message' => (int) $message['id_message'],
            'sender_type' => (string) $message['sender_type'],
            'isi_pesan' => (string) $message['isi_pesan'],
            'dibuat_pada' => (string) $message['dibuat_pada'],
            'waktu_label' => date('d M Y H:i', strtotime((string) $message['dibuat_pada'])),
        ], $messages);
    }

    public function roomContextForJson(?array $thread): ?array
    {
        if ($thread === null || empty($thread['id_kamar'])) {
            return null;
        }

        return [
            'id_kamar' => (int) $thread['id_kamar'],
            'title' => (string) (($thread['nama_kost'] ?? 'KosOnline') . ' - Kamar ' . ($thread['nomor_kamar'] ?? '-')),
            'subtitle' => (string) ($thread['alamat'] ?? ''),
            'harga' => (float) ($thread['harga'] ?? 0),
            'status' => (string) ($thread['status_kamar'] ?? '-'),
            'foto_kost' => (string) ($thread['foto_kost'] ?? ''),
            'detail_url' => url('/rooms/detail?id=' . (int) $thread['id_kamar']),
            'image_url' => upload_asset((string) ($thread['foto_kost'] ?? '')),
        ];
    }
}
