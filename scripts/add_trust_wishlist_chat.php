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

$hasTable = static function (string $table) use ($db): bool {
    return $db->selectOne(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$table]
    ) !== null;
};

if (!$hasTable('room_reviews')) {
    $changes[] = 'table:room_reviews';

    if ($applyChanges) {
        $db->execute(
            "CREATE TABLE `room_reviews` (
                `id_review` INT NOT NULL AUTO_INCREMENT,
                `id_user` INT NOT NULL,
                `id_kamar` INT NOT NULL,
                `rating` TINYINT UNSIGNED NOT NULL,
                `komentar` TEXT NOT NULL,
                `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `diubah_pada` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_review`),
                UNIQUE KEY `idx_room_reviews_user_room` (`id_user`, `id_kamar`),
                KEY `idx_room_reviews_room` (`id_kamar`),
                CONSTRAINT `fk_room_reviews_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
                CONSTRAINT `fk_room_reviews_room` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}

if (!$hasTable('room_wishlists')) {
    $changes[] = 'table:room_wishlists';

    if ($applyChanges) {
        $db->execute(
            "CREATE TABLE `room_wishlists` (
                `id_wishlist` INT NOT NULL AUTO_INCREMENT,
                `id_user` INT NOT NULL,
                `id_kamar` INT NOT NULL,
                `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_wishlist`),
                UNIQUE KEY `idx_room_wishlists_user_room` (`id_user`, `id_kamar`),
                KEY `idx_room_wishlists_room` (`id_kamar`),
                CONSTRAINT `fk_room_wishlists_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
                CONSTRAINT `fk_room_wishlists_room` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}

if (!$hasTable('chat_threads')) {
    $changes[] = 'table:chat_threads';

    if ($applyChanges) {
        $db->execute(
            "CREATE TABLE `chat_threads` (
                `id_thread` INT NOT NULL AUTO_INCREMENT,
                `id_user` INT NOT NULL,
                `id_kamar` INT NULL,
                `subjek` VARCHAR(160) NOT NULL DEFAULT 'Chat dengan Admin',
                `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
                `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `diupdate_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_thread`),
                KEY `idx_chat_threads_user` (`id_user`),
                KEY `idx_chat_threads_room` (`id_kamar`),
                CONSTRAINT `fk_chat_threads_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
                CONSTRAINT `fk_chat_threads_room` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}

if (!$hasTable('chat_messages')) {
    $changes[] = 'table:chat_messages';

    if ($applyChanges) {
        $db->execute(
            "CREATE TABLE `chat_messages` (
                `id_message` INT NOT NULL AUTO_INCREMENT,
                `id_thread` INT NOT NULL,
                `sender_type` ENUM('user','admin') NOT NULL,
                `tipe_pesan` ENUM('text','room_card') NOT NULL DEFAULT 'text',
                `id_kamar` INT NULL,
                `isi_pesan` TEXT NOT NULL,
                `dibaca` TINYINT(1) NOT NULL DEFAULT 0,
                `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_message`),
                KEY `idx_chat_messages_thread` (`id_thread`),
                KEY `idx_chat_messages_room` (`id_kamar`),
                CONSTRAINT `fk_chat_messages_thread` FOREIGN KEY (`id_thread`) REFERENCES `chat_threads` (`id_thread`) ON DELETE CASCADE,
                CONSTRAINT `fk_chat_messages_room` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}

echo json_encode([
    'mode' => $applyChanges ? 'applied' : 'dry-run',
    'changes' => $changes,
], JSON_PRETTY_PRINT) . PHP_EOL;
