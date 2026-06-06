<?php

declare(strict_types=1);

function base_path(string $path = ''): string
{
    $basePath = dirname(__DIR__);

    return $path === '' ? $basePath : $basePath . '/' . ltrim($path, '/');
}

function app_base_url(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $baseUrl = dirname($scriptName);

    if ($baseUrl === '/' || $baseUrl === '.') {
        return '';
    }

    return rtrim($baseUrl, '/');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    if (
        preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) === 1 ||
        str_starts_with($path, 'mailto:') ||
        str_starts_with($path, 'tel:') ||
        str_starts_with($path, '#')
    ) {
        return $path;
    }

    $baseUrl = app_base_url();
    $normalizedPath = trim($path);

    if ($normalizedPath === '' || $normalizedPath === '/') {
        return ($baseUrl !== '' ? $baseUrl : '') . '/';
    }

    return ($baseUrl !== '' ? $baseUrl : '') . '/' . ltrim($normalizedPath, '/');
}

function asset(string $path): string
{
    return url('public/assets/' . ltrim($path, '/'));
}

function upload_asset(?string $filename): string
{
    $safeFilename = trim((string) $filename);

    if ($safeFilename === '') {
        return asset('images/site/images.jpg');
    }

    return asset('images/uploads/' . rawurlencode($safeFilename));
}

function site_image(string $filename): string
{
    return asset('images/site/' . ltrim($filename, '/'));
}

function redirect_to(string $url): never
{
    header('Location: ' . url($url));
    exit;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $message;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function remember_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_token_is_valid(): bool
{
    $sessionToken = $_SESSION['_csrf_token'] ?? null;
    $requestToken = $_POST['_token'] ?? null;

    return is_string($sessionToken)
        && is_string($requestToken)
        && hash_equals($sessionToken, $requestToken);
}
