<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MessageModel;

final class ContactController extends Controller
{
    private MessageModel $messageModel;

    public function __construct()
    {
        $this->messageModel = new MessageModel();
    }

    public function index(): void
    {
        if ($this->isPost() && isset($_POST['kirim_pesan'])) {
            $name = trim((string) ($_POST['nama'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $message = trim((string) ($_POST['pesan'] ?? ''));

            if ($name === '' || $message === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                set_flash('error', 'Nama, email valid, dan isi pesan wajib diisi.');
                redirect_to('/contact');
            }

            $this->messageModel->create(
                $name,
                $email,
                $message
            );

            set_flash('success', 'Pesan berhasil dikirim! Kami akan segera menghubungi Anda via Email.');
            redirect_to('/contact');
        }

        $this->render('contact/index', [
            'successMessage' => flash('success'),
            'errorMessage' => flash('error'),
        ]);
    }
}
