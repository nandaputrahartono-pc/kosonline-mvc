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
        $recommendations = $this->kostModel->getRecommendations(10);
        $promos = $this->kostModel->getPromos(10);
        $branches = $this->kostModel->getAllWithAvailableCount();
        $availableRooms = array_sum(array_map(static fn (array $branch): int => (int) ($branch['kamar_tersedia'] ?? 0), $branches));
        $prices = array_map(static fn (array $room): float => (float) ($room['harga'] ?? 0), $recommendations);
        $prices = array_filter($prices, static fn (float $price): bool => $price > 0);

        $this->render('home/index', [
            'recommendations' => $recommendations,
            'promos' => $promos,
            'branches' => $branches,
            'homeStats' => [
                'total_branches' => count($branches),
                'available_rooms' => $availableRooms,
                'active_promos' => $this->kostModel->countAvailablePromos(),
                'starting_price' => $prices === [] ? 0 : min($prices),
            ],
        ]);
    }
}
