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
            $this->messageModel->create(
                trim((string) $_POST['nama']),
                trim((string) $_POST['email']),
                trim((string) $_POST['pesan'])
            );

            set_flash('success', 'Pesan berhasil dikirim! Kami akan segera menghubungi Anda via Email.');
            redirect_to('/contact');
        }

        $this->render('contact/index', [
            'successMessage' => flash('success'),
        ]);
    }
}
