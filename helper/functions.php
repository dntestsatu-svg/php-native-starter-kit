<?php

declare(strict_types=1);

use Mugiew\StarterKit\Core\Application;
use Mugiew\StarterKit\Services\Cache\MemcachedStore;
use Mugiew\StarterKit\Services\Security\CsrfManager;

if (!function_exists('app')) {
    function app(?string $id = null): mixed
    {
        $app = $GLOBALS['app'] ?? null;

        if (!$app instanceof Application) {
            throw new RuntimeException('Application is not bootstrapped yet.');
        }

        if ($id === null) {
            return $app;
        }

        return $app->container()->get($id);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        /** @var array<string, mixed> $config */
        $config = app()->container()->get('config');
        $segments = array_values(array_filter(explode('.', $key)));
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return app(CsrfManager::class)->issueToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $csrf = app(CsrfManager::class);
        $token = $csrf->issueToken();
        $fieldName = $csrf->tokenFieldName();

        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            e($fieldName),
            e($token)
        );
    }
}

if (!function_exists('cache_get')) {
    function cache_get(string $key, mixed $default = null): mixed
    {
        /** @var MemcachedStore|null $cache */
        $cache = app(MemcachedStore::class);

        if ($cache === null) {
            return $default;
        }

        return $cache->get($key, $default);
    }
}

if (!function_exists('cache_put')) {
    function cache_put(string $key, mixed $value, ?int $ttl = null): bool
    {
        /** @var MemcachedStore|null $cache */
        $cache = app(MemcachedStore::class);

        if ($cache === null) {
            return false;
        }

        return $cache->set($key, $value, $ttl);
    }
}

if (!function_exists('cache_remember')) {
    function cache_remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        /** @var MemcachedStore|null $cache */
        $cache = app(MemcachedStore::class);

        if ($cache === null) {
            return $callback();
        }

        return $cache->remember($key, $callback, $ttl);
    }
}
