<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class FileUploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

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

        $filename = basename((string) $file['name']);
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Format foto harus jpg, jpeg, png, atau webp.');
        }

        if (!is_uploaded_file((string) $file['tmp_name'])) {
            throw new RuntimeException('Upload file tidak valid.');
        }

        $target = $this->uploadDirectory . '/' . $filename;

        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0777, true);
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new RuntimeException('Gagal menyimpan foto ke folder upload.');
        }

        return $filename;
    }
}
