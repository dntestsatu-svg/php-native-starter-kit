<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Config;

use Mugiew\StarterKit\Support\Env;

final class Redis
{
    /**
     * @return array<string, mixed>
     */
    public static function fromEnv(array $env): array
    {
        return [
            'scheme' => Env::string($env, 'REDIS_SCHEME', 'tcp'),
            'host' => Env::string($env, 'REDIS_HOST', '127.0.0.1'),
            'port' => Env::int($env, 'REDIS_PORT', 6379),
            'password' => Env::nullableString($env, 'REDIS_PASSWORD'),
            'database' => Env::int($env, 'REDIS_DATABASE', 0),
            'timeout' => Env::float($env, 'REDIS_TIMEOUT', 5.0),
        ];
    }
}
