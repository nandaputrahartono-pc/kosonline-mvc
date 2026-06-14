<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\WishlistModel;

final class WishlistController extends Controller
{
    private WishlistModel $wishlistModel;

    public function __construct()
    {
        $this->wishlistModel = new WishlistModel();
    }

    public function toggle(): void
    {
        $this->requireUser();

        $roomId = (int) ($_POST['id_kamar'] ?? 0);
        $redirect = (string) ($_POST['redirect'] ?? '/rooms');

        if ($roomId <= 0) {
            if ($this->expectsJson()) {
                $this->json(['ok' => false, 'message' => 'Kamar tidak valid.'], 422);
            }

            set_flash('error', 'Kamar tidak valid.');
            redirect_to($redirect);
        }

        $saved = $this->wishlistModel->toggle((int) $_SESSION['id_user'], $roomId);
        $message = $saved ? 'Kamar disimpan ke wishlist.' : 'Kamar dihapus dari wishlist.';

        if ($this->expectsJson()) {
            $this->json([
                'ok' => true,
                'saved' => $saved,
                'message' => $message,
                'room_id' => $roomId,
            ]);
        }

        set_flash('success', $message);
        redirect_to($redirect);
    }

    private function expectsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'fetch';
    }

    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
