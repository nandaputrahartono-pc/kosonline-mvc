<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RoomGalleryModel extends Model
{
    public function getByRoomId(int $roomId): array
    {
        return $this->db->selectAll(
            'SELECT * FROM galeri_kamar WHERE id_kamar = ? ORDER BY urutan ASC, id_galeri ASC',
            [$roomId]
        );
    }

    public function findByIdForRoom(int $galleryId, int $roomId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM galeri_kamar WHERE id_galeri = ? AND id_kamar = ?',
            [$galleryId, $roomId]
        );
    }

    public function create(int $roomId, array $data): int
    {
        return $this->db->insert(
            'INSERT INTO galeri_kamar (id_kamar, kategori, judul, nama_file, urutan)
             VALUES (?, ?, ?, ?, ?)',
            [
                $roomId,
                $data['kategori'],
                $data['judul'],
                $data['nama_file'],
                $data['urutan'],
            ]
        );
    }

    public function update(int $galleryId, int $roomId, array $data): bool
    {
        return $this->db->execute(
            'UPDATE galeri_kamar
             SET kategori = ?, judul = ?, urutan = ?
             WHERE id_galeri = ? AND id_kamar = ?',
            [
                $data['kategori'],
                $data['judul'],
                $data['urutan'],
                $galleryId,
                $roomId,
            ]
        );
    }

    public function delete(int $galleryId, int $roomId): bool
    {
        return $this->db->execute(
            'DELETE FROM galeri_kamar WHERE id_galeri = ? AND id_kamar = ?',
            [$galleryId, $roomId]
        );
    }
}
