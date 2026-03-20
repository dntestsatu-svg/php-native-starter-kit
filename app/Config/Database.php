<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Config;

use Mugiew\StarterKit\Support\Env;
use PDO;

final class Database
{
    /**
     * @return array<string, mixed>
     */
    public static function fromEnv(array $env): array
    {
        $driver = Env::string($env, 'DB_CONNECTION', 'mysql');

        if ($driver === 'sqlite') {
            $database = Env::string($env, 'DB_DATABASE', 'database/database.sqlite');

            return [
                'driver' => 'sqlite',
                'database' => $database,
                'username' => '',
                'password' => '',
                'dsn' => sprintf('sqlite:%s', $database),
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ];
        }

        $host = Env::string($env, 'DB_HOST', '127.0.0.1');
        $port = Env::int($env, 'DB_PORT', 3306);
        $database = Env::string($env, 'DB_DATABASE', 'native');
        $charset = Env::string($env, 'DB_CHARSET', 'utf8mb4');

        return [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => Env::string($env, 'DB_USERNAME', 'root'),
            'password' => Env::string($env, 'DB_PASSWORD', ''),
            'charset' => $charset,
            'collation' => Env::string($env, 'DB_COLLATION', 'utf8mb4_unicode_ci'),
            'dsn' => sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $host,
                $port,
                $database,
                $charset
            ),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];
    }
}
