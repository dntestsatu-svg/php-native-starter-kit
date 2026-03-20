<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Config;

use Mugiew\StarterKit\Support\Env;

final class Security
{
    /**
     * @return array<string, mixed>
     */
    public static function fromEnv(array $env): array
    {
        $except = Env::string($env, 'CSRF_EXCEPT', '');
        $exceptList = array_values(array_filter(array_map('trim', explode(',', $except))));

        return [
            'csrf' => [
                'enabled' => Env::bool($env, 'CSRF_ENABLED', true),
                'token_field' => Env::string($env, 'CSRF_TOKEN_FIELD', '_token'),
                'header' => Env::string($env, 'CSRF_HEADER', 'X-CSRF-TOKEN'),
                'ttl' => Env::int($env, 'CSRF_TTL', 7200),
                'prefix' => Env::string($env, 'CSRF_PREFIX', 'native_csrf:'),
                'except' => $exceptList,
            ],
            'rate_limit' => [
                'enabled' => Env::bool($env, 'RATE_LIMIT_ENABLED', true),
                'prefix' => Env::string($env, 'RATE_LIMIT_PREFIX', 'native_rate_limit:'),
                'login' => [
                    'max_attempts' => Env::int($env, 'LOGIN_RATE_LIMIT_MAX_ATTEMPTS', 5),
                    'decay_seconds' => Env::int($env, 'LOGIN_RATE_LIMIT_DECAY_SECONDS', 60),
                ],
                'register' => [
                    'max_attempts' => Env::int($env, 'REGISTER_RATE_LIMIT_MAX_ATTEMPTS', 3),
                    'decay_seconds' => Env::int($env, 'REGISTER_RATE_LIMIT_DECAY_SECONDS', 300),
                ],
            ],
        ];
    }
}
