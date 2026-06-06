<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<string, array<string, callable|array{0: class-string, 1: string}>>
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $normalizedMethod = strtoupper($method);
        $path = $this->normalizeRequestPath($uri);

        $handler = $this->routes[$normalizedMethod][$path] ?? null;

        if ($handler === null && $normalizedMethod === 'HEAD') {
            $handler = $this->routes['GET'][$path] ?? null;
        }

        if ($handler === null) {
            http_response_code(404);
            echo '404 - Halaman tidak ditemukan.';
            return;
        }

        if ($normalizedMethod === 'POST' && !csrf_token_is_valid()) {
            http_response_code(419);
            echo '419 - Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
            return;
        }

        if (is_array($handler)) {
            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass();
            $controller->{$action}();
            return;
        }

        $handler();
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$this->normalizePath($path)] = $handler;
    }

    private function normalizeRequestPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $baseUrl = app_base_url();

        if ($baseUrl !== '' && str_starts_with($path, $baseUrl)) {
            $path = substr($path, strlen($baseUrl)) ?: '/';
        }

        return $this->normalizePath($path);
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');

        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }
}
