<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?self $instance = null;
    
    private PDO $connection;

    private function __construct(array $config)
    {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            throw new RuntimeException('Tidak dapat terhubung ke database.', 0, $e);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            /** @var array<string, string> $config */
            $config = require base_path('config/database.php');
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function connection(): PDO
    {
        return $this->connection;
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    public function selectAll(string $sql, array $params = []): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        
        return $statement->fetchAll();
    }

    public function selectOne(string $sql, array $params = []): ?array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $result = $statement->fetch();
        
        return $result !== false ? $result : null;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $statement = $this->connection->prepare($sql);
        
        return $statement->execute($params);
    }

    public function insert(string $sql, array $params = []): int
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        
        return (int) $this->connection->lastInsertId();
    }
}
