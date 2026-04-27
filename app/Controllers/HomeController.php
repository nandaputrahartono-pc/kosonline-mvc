<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\KostModel;

final class HomeController extends Controller
{
    private KostModel $kostModel;

    public function __construct()
    {
        $this->kostModel = new KostModel();
    }

    public function index(): void
    {
        $this->render('home/index', [
            'recommendations' => $this->kostModel->getRecommendations(),
        ]);
    }
}
