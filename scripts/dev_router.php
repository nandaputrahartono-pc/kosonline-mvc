<?php

declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$requestedFile = dirname(__DIR__) . '/' . ltrim($path, '/');

if ($path !== '/' && is_file($requestedFile)) {
    return false;
}

require dirname(__DIR__) . '/index.php';
