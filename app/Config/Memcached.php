<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Config;

use Mugiew\StarterKit\Support\Env;

final class Memcached
{
    /**
     * @return array<string, mixed>
     */
    public static function fromEnv(array $env): array
    {
        $appName = strtolower(preg_replace('/\s+/', '_', Env::string($env, 'APP_NAME', 'native')) ?? 'native');

        return [
            'host' => Env::string($env, 'MEMCACHED_HOST', '127.0.0.1'),
            'port' => Env::int($env, 'MEMCACHED_PORT', 11211),
            'weight' => Env::int($env, 'MEMCACHED_WEIGHT', 100),
            'persistent_id' => Env::nullableString($env, 'MEMCACHED_PERSISTENT_ID'),
            'timeout' => Env::int($env, 'MEMCACHED_TIMEOUT', 1),
            'retry_interval' => Env::int($env, 'MEMCACHED_RETRY_INTERVAL', 2),
            'prefix' => Env::string($env, 'CACHE_PREFIX', $appName),
            'default_ttl' => Env::int($env, 'CACHE_DEFAULT_TTL', 300),
            'env_ttl' => Env::int($env, 'CACHE_ENV_TTL', 600),
            'config_ttl' => Env::int($env, 'CACHE_CONFIG_TTL', 600),
        ];
    }
}
