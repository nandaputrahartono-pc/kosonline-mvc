<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\RoomModel;

final class RoomController extends Controller
{
    private RoomModel $roomModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
    }

    public function index(): void
    {
        $keyword = trim((string) ($_GET['cari'] ?? ''));

        $this->render('room/index', [
            'keyword' => $keyword,
            'rooms' => $this->roomModel->searchAvailable($keyword),
        ]);
    }
}
