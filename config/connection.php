<?php

declare(strict_types=1);

/**
 * Legacy compatibility shim.
 *
 * New code should use config('database'), config('redis'), and config('memcached') directly.
 */
function getDatabaseConfig(): array
{
    if (!function_exists('config') || !isset($GLOBALS['app'])) {
        return ['database' => ['default' => []]];
    }

    return ['database' => ['default' => config('database', [])]];
}

function getRedisConfig(): array
{
    if (!function_exists('config') || !isset($GLOBALS['app'])) {
        return ['redis' => []];
    }

    return ['redis' => config('redis', [])];
}

function getMemcachedConfig(): array
{
    if (!function_exists('config') || !isset($GLOBALS['app'])) {
        return ['memcached' => []];
    }

    return ['memcached' => config('memcached', [])];
}
