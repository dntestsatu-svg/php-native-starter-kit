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
            /** @var array<string, mixed> $cached */
            $cached = $this->cache->remember(
                $cacheKey,
                fn (): array => $this->compile($memcached),
                $ttl
            );

            return $cached;
        }

        return $this->compile($memcached);
    }

    private function configCacheKey(): string
    {
        $normalizedEnv = $this->env;
        ksort($normalizedEnv);

        return 'config:' . hash('sha256', json_encode($normalizedEnv, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $memcached
     * @return array<string, mixed>
     */
    private function compile(array $memcached): array
    {
        return [
            'app' => App::fromEnv($this->env),
            'database' => Database::fromEnv($this->env),
            'redis' => Redis::fromEnv($this->env),
            'memcached' => $memcached,
            'session' => Session::fromEnv($this->env),
            'security' => Security::fromEnv($this->env),
        ];
    }
}
