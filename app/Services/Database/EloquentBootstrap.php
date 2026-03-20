<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

final class EloquentBootstrap
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function boot(): Capsule
    {
        $capsule = new Capsule();
        $capsule->addConnection($this->connectionConfig());
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $capsule;
    }

    /**
     * @return array<string, mixed>
     */
    private function connectionConfig(): array
    {
        $driver = (string) ($this->config['driver'] ?? 'mysql');

        if ($driver === 'sqlite') {
            return [
                'driver' => 'sqlite',
                'database' => (string) ($this->config['database'] ?? 'database/database.sqlite'),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

        return [
            'driver' => $driver,
            'host' => (string) ($this->config['host'] ?? '127.0.0.1'),
            'port' => (int) ($this->config['port'] ?? 3306),
            'database' => (string) ($this->config['database'] ?? 'native'),
            'username' => (string) ($this->config['username'] ?? 'root'),
            'password' => (string) ($this->config['password'] ?? ''),
            'charset' => (string) ($this->config['charset'] ?? 'utf8mb4'),
            'collation' => (string) ($this->config['collation'] ?? 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'strict' => true,
        ];
    }
}
