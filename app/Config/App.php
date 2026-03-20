<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Config;

use Mugiew\StarterKit\Support\Env;

final class App
{
    /**
     * @return array<string, mixed>
     */
    public static function fromEnv(array $env): array
    {
        return [
            'name' => Env::string($env, 'APP_NAME', 'Native Starter'),
            'env' => Env::string($env, 'APP_ENV', 'production'),
            'debug' => Env::bool($env, 'APP_DEBUG', false),
            'url' => Env::string($env, 'APP_URL', 'http://localhost'),
            'timezone' => Env::string($env, 'APP_TIMEZONE', 'UTC'),
        ];
    }
}
