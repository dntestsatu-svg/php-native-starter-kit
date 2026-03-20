<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Config;

use Dotenv\Dotenv;
use Mugiew\StarterKit\Services\Cache\MemcachedStore;
use Mugiew\StarterKit\Support\Env;

final class EnvironmentLoader
{
    public function __construct(
        private readonly string $basePath,
    ) {}

    public function load(): EnvironmentBootstrap
    {
        $envFile = $this->basePath . DIRECTORY_SEPARATOR . '.env';
        $raw = $this->parseRawEnv($envFile);
        $bootstrapCache = MemcachedStore::create([
            'host' => $raw['MEMCACHED_HOST'] ?? '127.0.0.1',
            'port' => (int) ($raw['MEMCACHED_PORT'] ?? 11211),
            'weight' => 100,
            'timeout' => (int) ($raw['MEMCACHED_TIMEOUT'] ?? 1),
            'retry_interval' => (int) ($raw['MEMCACHED_RETRY_INTERVAL'] ?? 2),
            'prefix' => 'native_bootstrap',
            'default_ttl' => (int) ($raw['CACHE_ENV_TTL'] ?? 600),
        ], true);

        $cacheKey = $this->environmentCacheKey($envFile);
        $envTtl = (int) ($raw['CACHE_ENV_TTL'] ?? 600);

        if ($bootstrapCache !== null) {
            $cached = $bootstrapCache->get($cacheKey);

            if (is_array($cached)) {
                $this->hydrateEnvironment($cached);
                return new EnvironmentBootstrap($cached, $bootstrapCache);
            }
        }

        if (is_file($envFile)) {
            Dotenv::createImmutable($this->basePath)->safeLoad();
        }

        $snapshot = $this->snapshotEnvironment();

        if ($bootstrapCache !== null && $snapshot !== []) {
            $bootstrapCache->set($cacheKey, $snapshot, $envTtl);
        }

        return new EnvironmentBootstrap($snapshot, $bootstrapCache);
    }

    private function environmentCacheKey(string $envFile): string
    {
        if (!is_file($envFile)) {
            return 'env:missing';
        }

        $hash = sha1_file($envFile);
        return 'env:' . ($hash ?: 'unknown');
    }

    /**
     * @return array<string, string>
     */
    private function snapshotEnvironment(): array
    {
        $snapshot = [];

        foreach ($_ENV as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            $snapshot[$key] = (string) $value;
        }

        return $snapshot;
    }

    /**
     * @param array<string, mixed> $env
     */
    private function hydrateEnvironment(array $env): void
    {
        foreach ($env as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $scalar = is_scalar($value) ? (string) $value : '';
            $_ENV[$key] = $scalar;
            $_SERVER[$key] = $scalar;
            putenv(sprintf('%s=%s', $key, $scalar));
        }
    }

    /**
     * @return array<string, string>
     */
    private function parseRawEnv(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!is_array($lines)) {
            return [];
        }

        $parsed = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim($value);

            if ($value !== '' && $this->isQuoted($value)) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                $parsed[$key] = (string) Env::get([$key => $value], $key, '');
            }
        }

        return $parsed;
    }

    private function isQuoted(string $value): bool
    {
        return (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"));
    }
}
