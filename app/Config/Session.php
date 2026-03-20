<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Config;

use Mugiew\StarterKit\Support\Env;

final class Session
{
    /**
     * @return array<string, mixed>
     */
    public static function fromEnv(array $env): array
    {
        return [
            'driver' => Env::string($env, 'SESSION_DRIVER', 'redis'),
            'lifetime' => Env::int($env, 'SESSION_LIFETIME', 120) * 60,
            'cookie' => Env::string($env, 'SESSION_COOKIE', 'native_session'),
            'path' => Env::string($env, 'SESSION_PATH', '/'),
            'domain' => Env::nullableString($env, 'SESSION_DOMAIN'),
            'secure' => Env::bool($env, 'SESSION_SECURE_COOKIE', false),
            'http_only' => Env::bool($env, 'SESSION_HTTP_ONLY', true),
            'same_site' => Env::string($env, 'SESSION_SAME_SITE', 'Lax'),
            'prefix' => Env::string($env, 'SESSION_PREFIX', 'native_session:'),
        ];
    }
}
