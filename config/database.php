<?php

declare(strict_types=1);

$localConfig = __DIR__ . '/database.local.php';
if (is_file($localConfig)) {
    return require $localConfig;
}

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => getenv('DB_DATABASE') ?: 'kosonline',
];
