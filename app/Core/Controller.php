<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
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
        if (!isset($_SESSION['id_user'])) {
            set_flash('error', 'Silakan login terlebih dahulu.');
            redirect_to('/login');
        }
    }
}
