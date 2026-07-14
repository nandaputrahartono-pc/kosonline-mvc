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
    return $db->selectOne(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column]
    ) !== null;
};

$hasTable = static function (string $table) use ($db): bool {
    return $db->selectOne(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$table]
    ) !== null;
};

$hasIndex = static function (string $table, string $index) use ($db): bool {
    return $db->selectOne(
        'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
        [$table, $index]
    ) !== null;
};

if (!$hasColumn('chat_messages', 'tipe_pesan')) {
    $changes[] = 'column:chat_messages.tipe_pesan';
    if ($applyChanges) {
        $db->execute(
            "ALTER TABLE chat_messages
             ADD COLUMN tipe_pesan ENUM('text','room_card') NOT NULL DEFAULT 'text' AFTER sender_type"
        );
    }
}

if (!$hasColumn('chat_messages', 'id_kamar')) {
    $changes[] = 'column:chat_messages.id_kamar';
    if ($applyChanges) {
        $db->execute('ALTER TABLE chat_messages ADD COLUMN id_kamar INT NULL AFTER tipe_pesan');
    }
}

if (!$hasIndex('chat_messages', 'idx_chat_messages_room')) {
    $changes[] = 'index:chat_messages.idx_chat_messages_room';
    if ($applyChanges) {
        $db->execute('ALTER TABLE chat_messages ADD KEY idx_chat_messages_room (id_kamar)');
    }
}

if ($applyChanges) {
    $hasTypingTable = $hasTable('chat_typing');
    $threads = $db->selectAll(
        'SELECT id_thread, id_user, id_kamar, subjek, dibuat_pada, diupdate_pada
         FROM chat_threads
         ORDER BY id_user ASC, id_thread ASC'
    );

    $threadsByUser = [];
    foreach ($threads as $thread) {
        $threadsByUser[(int) $thread['id_user']][] = $thread;
    }

    foreach ($threadsByUser as $userId => $userThreads) {
        $primary = $userThreads[0];
        $primaryId = (int) $primary['id_thread'];

        foreach ($userThreads as $thread) {
            $threadId = (int) $thread['id_thread'];
            $roomId = (int) ($thread['id_kamar'] ?? 0);

            if ($roomId > 0) {
                $existingCard = $db->selectOne(
                    "SELECT id_message
                     FROM chat_messages
                     WHERE id_thread = ? AND tipe_pesan = 'room_card' AND id_kamar = ?
                     LIMIT 1",
                    [$threadId, $roomId]
                );

                if ($existingCard === null) {
                    $firstMessage = $db->selectOne(
                        'SELECT dibuat_pada FROM chat_messages WHERE id_thread = ? ORDER BY dibuat_pada ASC, id_message ASC LIMIT 1',
                        [$threadId]
                    );
                    $cardTime = (string) ($firstMessage['dibuat_pada'] ?? $thread['dibuat_pada']);

                    $db->insert(
                        "INSERT INTO chat_messages (id_thread, sender_type, tipe_pesan, id_kamar, isi_pesan, dibuat_pada)
                         VALUES (?, 'user', 'room_card', ?, '', ?)",
                        [$threadId, $roomId, $cardTime]
                    );
                }
            }

            if ($threadId !== $primaryId) {
                $db->execute('UPDATE chat_messages SET id_thread = ? WHERE id_thread = ?', [$primaryId, $threadId]);
                if ($hasTypingTable) {
                    $db->execute('DELETE FROM chat_typing WHERE id_thread = ?', [$threadId]);
                }
                $db->execute('DELETE FROM chat_threads WHERE id_thread = ?', [$threadId]);
            }
        }

        $latestMessage = $db->selectOne(
            'SELECT dibuat_pada FROM chat_messages WHERE id_thread = ? ORDER BY dibuat_pada DESC, id_message DESC LIMIT 1',
            [$primaryId]
        );
        $updatedAt = (string) ($latestMessage['dibuat_pada'] ?? $primary['diupdate_pada']);

        $db->execute(
            "UPDATE chat_threads
             SET id_kamar = NULL, subjek = 'Chat dengan Admin', diupdate_pada = ?
             WHERE id_thread = ?",
            [$updatedAt, $primaryId]
        );
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'changes' => $changes,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
