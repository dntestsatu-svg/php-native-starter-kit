<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Support;

use Predis\Client;

final class InMemoryRedisClient extends Client
{
    /**
     * @var array<string, string>
     */
    private array $store = [];

    /**
     * @var array<string, int>
     */
    private array $expiresAt = [];

    public function __construct() {}

    public function setex($key, $ttl, $value): bool
    {
        $normalized = (string) $key;
        $this->store[$normalized] = (string) $value;
        $this->expiresAt[$normalized] = time() + max(1, (int) $ttl);

        return true;
    }

    public function incr($key): int
    {
        $normalized = (string) $key;
        $this->purgeIfExpired($normalized);

        $current = (int) ($this->store[$normalized] ?? 0);
        $current++;

        $this->store[$normalized] = (string) $current;

        return $current;
    }

    public function expire($key, $ttl): int
    {
        $normalized = (string) $key;
        $this->purgeIfExpired($normalized);

        if (!array_key_exists($normalized, $this->store)) {
            return 0;
        }

        $this->expiresAt[$normalized] = time() + max(1, (int) $ttl);

        return 1;
    }

    public function ttl($key): int
    {
        $normalized = (string) $key;
        $this->purgeIfExpired($normalized);

        if (!array_key_exists($normalized, $this->store)) {
            return -2;
        }

        if (!array_key_exists($normalized, $this->expiresAt)) {
            return -1;
        }

        return max(0, $this->expiresAt[$normalized] - time());
    }

    public function get($key): ?string
    {
        $normalized = (string) $key;
        $this->purgeIfExpired($normalized);

        if (!array_key_exists($normalized, $this->store)) {
            return null;
        }

        return $this->store[$normalized];
    }

    public function del($keys): int
    {
        $deleted = 0;
        $keys = is_array($keys) ? $keys : [$keys];

        foreach ($keys as $key) {
            $normalized = (string) $key;
            $this->purgeIfExpired($normalized);

            if (!array_key_exists($normalized, $this->store)) {
                continue;
            }

            unset($this->store[$normalized], $this->expiresAt[$normalized]);
            $deleted++;
        }

        return $deleted;
    }

    private function purgeIfExpired(string $key): void
    {
        if (!array_key_exists($key, $this->expiresAt)) {
            return;
        }

        if ($this->expiresAt[$key] > time()) {
            return;
        }

        unset($this->expiresAt[$key], $this->store[$key]);
    }
}
