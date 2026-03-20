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
        ];
    }
}
