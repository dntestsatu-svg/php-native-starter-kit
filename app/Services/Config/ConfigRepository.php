<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Config;

use Mugiew\StarterKit\Config\App;
use Mugiew\StarterKit\Config\Database;
use Mugiew\StarterKit\Config\Memcached;
use Mugiew\StarterKit\Config\Redis;
use Mugiew\StarterKit\Config\Security;
use Mugiew\StarterKit\Config\Session;
use Mugiew\StarterKit\Services\Cache\MemcachedStore;

final class ConfigRepository
{
    /**
     * @param array<string, string> $env
     */
    public function __construct(
        private readonly array $env,
        private readonly ?MemcachedStore $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $memcached = Memcached::fromEnv($this->env);
        $ttl = (int) ($memcached['config_ttl'] ?? 600);
        $cacheKey = $this->configCacheKey();

        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);

            if (is_array($cached)) {
                return $cached;
            }
        }

        $compiled = [
            'app' => App::fromEnv($this->env),
            'database' => Database::fromEnv($this->env),
            'redis' => Redis::fromEnv($this->env),
            'memcached' => $memcached,
            'session' => Session::fromEnv($this->env),
            'security' => Security::fromEnv($this->env),
        ];

        if ($this->cache !== null) {
            $this->cache->set($cacheKey, $compiled, $ttl);
        }

        return $compiled;
    }

    private function configCacheKey(): string
    {
        return 'config:' . hash('sha256', json_encode($this->env, JSON_THROW_ON_ERROR));
    }
}
