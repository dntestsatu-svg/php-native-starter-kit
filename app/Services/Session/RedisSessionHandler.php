<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Session;

use Predis\Client;
use SessionHandlerInterface;
use Throwable;

final class RedisSessionHandler implements SessionHandlerInterface
{
    public function __construct(
        private readonly Client $redis,
        private readonly string $prefix,
        private readonly int $ttl,
    ) {}

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $value = $this->redis->get($this->key($id));
            return $value === null ? '' : (string) $value;
        } catch (Throwable) {
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $result = $this->redis->setex($this->key($id), $this->ttl, $data);
            return (string) $result === 'OK';
        } catch (Throwable) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $this->redis->del([$this->key($id)]);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    private function key(string $id): string
    {
        return $this->prefix . $id;
    }
}
