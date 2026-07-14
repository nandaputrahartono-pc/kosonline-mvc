<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\ChatModel;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $this->withGlobalViewData($data));
    }

    /**
     * Data yang dibutuhkan layout/navbar di SEMUA halaman.
     * Ditaruh di sini supaya view tak perlu melakukan query sendiri (MVC tetap bersih).
     */
    private function withGlobalViewData(array $data): array
    {
        if (!array_key_exists('chatUnreadCount', $data)) {
            $data['chatUnreadCount'] = ($_SESSION['status'] ?? null) === 'login_user' && isset($_SESSION['id_user'])
                ? (new ChatModel())->countUnreadForUser((int) $_SESSION['id_user'])
                : 0;
        }

        return $data;
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    protected function request(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function requireAdmin(): void
    {
        if (($_SESSION['status'] ?? null) !== 'login_admin') {
            set_flash('error', 'Silakan login admin terlebih dahulu.');
            redirect_to('/admin/login');
        }
    }

    protected function requireUser(): void
    {
        if (($_SESSION['status'] ?? null) !== 'login_user' || !isset($_SESSION['id_user'])) {
            set_flash('error', 'Silakan login terlebih dahulu.');
            redirect_to('/login');
        }
    }
}
