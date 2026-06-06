<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class FileUploadService
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private string $uploadDirectory;

    public function __construct(?string $uploadDirectory = null)
    {
        $this->uploadDirectory = $uploadDirectory ?? base_path('public/assets/images/uploads');
    }

    /**
     * Upload sebuah file gambar dan kembalikan nama file-nya.
     *
     * @param array|null $file     Data dari $_FILES
     * @param bool       $required Apakah file wajib diupload
     *
     * @return string|null Nama file yang tersimpan, atau null jika tidak ada file
     *
     * @throws RuntimeException Jika validasi gagal atau upload gagal
     */
    public function upload(?array $file, bool $required = true): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new RuntimeException('Foto wajib dipilih.');
            }

            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload foto gagal. Silakan pilih file lain.');
        }

        $temporaryFile = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($temporaryFile)) {
            throw new RuntimeException('Upload file tidak valid.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Ukuran foto maksimal 5 MB.');
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
        $extension = self::ALLOWED_MIME_TYPES[$mimeType] ?? null;

        if ($extension === null || @getimagesize($temporaryFile) === false) {
            throw new RuntimeException('File harus berupa gambar JPG, PNG, atau WebP yang valid.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $this->uploadDirectory . '/' . $filename;

        if (!is_dir($this->uploadDirectory)) {
            if (!mkdir($this->uploadDirectory, 0755, true) && !is_dir($this->uploadDirectory)) {
                throw new RuntimeException('Folder upload tidak dapat dibuat.');
            }
        }

        if (!move_uploaded_file($temporaryFile, $target)) {
            throw new RuntimeException('Gagal menyimpan foto ke folder upload.');
        }

        return $filename;
    }

    public function delete(?string $filename): void
    {
        $safeFilename = basename(trim((string) $filename));
        if ($safeFilename === '' || $safeFilename !== trim((string) $filename)) {
            return;
        }

        $target = $this->uploadDirectory . '/' . $safeFilename;
        if (is_file($target)) {
            @unlink($target);
        }
    }
}
