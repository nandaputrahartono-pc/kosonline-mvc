<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MessageModel;

final class AdminMessageController extends Controller
{
    private MessageModel $messageModel;

    public function __construct()
    {
        $this->messageModel = new MessageModel();
    }

    public function delete(): void
    {
        $this->requireAdmin();

        $messageId = (int) ($_GET['id'] ?? 0);
        $this->messageModel->delete($messageId);
        set_flash('success', 'Pesan dihapus');
        redirect_to('/admin/dashboard');
    }
}
