<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Session;

use Predis\Client;
use RuntimeException;
use Throwable;

final class SessionManager
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly Client $redis,
        private readonly array $config,
    ) {}

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (($this->config['driver'] ?? 'redis') !== 'redis') {
            throw new RuntimeException('This starter kit is configured to use Redis sessions only.');
        }

        try {
            $pong = $this->redis->ping();

            if (strtoupper((string) $pong) !== 'PONG') {
                throw new RuntimeException('Redis PING did not return PONG.');
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Unable to connect to Redis for session handling: ' . $exception->getMessage(),
                previous: $exception
            );
        }

        $handler = new RedisSessionHandler(
            $this->redis,
            (string) ($this->config['prefix'] ?? 'native_session:'),
            (int) ($this->config['lifetime'] ?? 7200)
        );

        session_set_save_handler($handler, true);
        session_name((string) ($this->config['cookie'] ?? 'native_session'));
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', ($this->config['http_only'] ?? true) ? '1' : '0');
        ini_set('session.cookie_secure', ($this->config['secure'] ?? false) ? '1' : '0');
        ini_set('session.cookie_samesite', (string) ($this->config['same_site'] ?? 'Lax'));
        ini_set('session.gc_maxlifetime', (string) ($this->config['lifetime'] ?? 7200));

        session_set_cookie_params([
            'lifetime' => (int) ($this->config['lifetime'] ?? 7200),
            'path' => (string) ($this->config['path'] ?? '/'),
            'domain' => (string) ($this->config['domain'] ?? ''),
            'secure' => (bool) ($this->config['secure'] ?? false),
            'httponly' => (bool) ($this->config['http_only'] ?? true),
            'samesite' => (string) ($this->config['same_site'] ?? 'Lax'),
        ]);

        session_start();
    }
}
