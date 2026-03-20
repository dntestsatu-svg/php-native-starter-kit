<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Cache;

use RuntimeException;

final class MemcachedStore
{
    private function __construct(
        private readonly \Memcached $client,
        private readonly string $prefix,
        private readonly int $defaultTtl,
    ) {}

    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config, bool $failSilently = false): ?self
    {
        if (!extension_loaded('memcached')) {
            if ($failSilently) {
                return null;
            }

            throw new RuntimeException('Memcached extension is required but not loaded.');
        }

        $persistentId = $config['persistent_id'] ?? null;
        $memcached = new \Memcached(is_string($persistentId) && $persistentId !== '' ? $persistentId : null);
        $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, (int) (($config['timeout'] ?? 1) * 1000));
        $memcached->setOption(\Memcached::OPT_RETRY_TIMEOUT, (int) ($config['retry_interval'] ?? 2));
        $memcached->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
        $memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

        if ($memcached->getServerList() === []) {
            $added = $memcached->addServer(
                (string) ($config['host'] ?? '127.0.0.1'),
                (int) ($config['port'] ?? 11211),
                (int) ($config['weight'] ?? 100)
            );

            if (!$added && !$failSilently) {
                throw new RuntimeException('Unable to add Memcached server.');
            }
        }

        $store = new self(
            $memcached,
            (string) ($config['prefix'] ?? 'native'),
            (int) ($config['default_ttl'] ?? 300)
        );

        if (!$failSilently && !$store->healthy()) {
            throw new RuntimeException('Memcached server is not reachable.');
        }

        return $store;
    }

    public function healthy(): bool
    {
        $versions = $this->client->getVersion();

        if (!is_array($versions) || $versions === []) {
            return false;
        }

        foreach ($versions as $version) {
            if ($version === false || $version === '255.255.255') {
                continue;
            }

            return true;
        }

        return false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->client->get($this->prefix($key));

        if ($value === false && $this->client->getResultCode() !== \Memcached::RES_SUCCESS) {
            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        return $this->client->set($this->prefix($key), $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->client->delete($this->prefix($key));
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cached = $this->get($key, null);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    private function prefix(string $key): string
    {
        return $this->prefix . ':' . ltrim($key, ':');
    }
}
