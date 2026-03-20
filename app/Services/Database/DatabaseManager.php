<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Database;

use PDO;

final class DatabaseManager
{
    private ?PDO $connection = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function connection(): PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        /** @var array<int, mixed> $options */
        $options = $this->config['options'] ?? [];

        $this->connection = new PDO(
            (string) $this->config['dsn'],
            (string) $this->config['username'],
            (string) $this->config['password'],
            $options
        );

        return $this->connection;
    }
}
