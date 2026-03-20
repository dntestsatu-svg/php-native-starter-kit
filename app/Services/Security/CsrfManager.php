<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Security;

use Mugiew\StarterKit\Core\Request;
use Predis\Client;
use Throwable;

final class CsrfManager
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly Client $redis,
        private readonly array $config,
    ) {}

    public function tokenFieldName(): string
    {
        return (string) ($this->config['token_field'] ?? '_token');
    }

    public function issueToken(): string
    {
        $sessionId = session_id();

        if ($sessionId === '') {
            return '';
        }

        $token = bin2hex(random_bytes(32));
        $key = $this->key($sessionId, $token);
        $ttl = (int) ($this->config['ttl'] ?? 7200);

        $this->redis->setex($key, $ttl, '1');

        return $token;
    }

    public function verify(Request $request): bool
    {
        if (!($this->config['enabled'] ?? true)) {
            return true;
        }

        $sessionId = session_id();

        if ($sessionId === '') {
            return false;
        }

        $token = $this->extractToken($request);

        if ($token === null || $token === '') {
            return false;
        }

        $key = $this->key($sessionId, $token);

        try {
            $deleted = $this->redis->del([$key]);
            return (int) $deleted === 1;
        } catch (Throwable) {
            return false;
        }
    }

    private function extractToken(Request $request): ?string
    {
        $field = (string) ($this->config['token_field'] ?? '_token');
        $headerName = (string) ($this->config['header'] ?? 'X-CSRF-TOKEN');

        $fromBody = $request->input($field);
        if (is_scalar($fromBody) && $fromBody !== '') {
            return (string) $fromBody;
        }

        $fromHeader = $request->header($headerName);
        if ($fromHeader !== null && $fromHeader !== '') {
            return $fromHeader;
        }

        $fromFallbackHeader = $request->header('X-XSRF-TOKEN');
        if ($fromFallbackHeader !== null && $fromFallbackHeader !== '') {
            return $fromFallbackHeader;
        }

        return null;
    }

    private function key(string $sessionId, string $token): string
    {
        $prefix = (string) ($this->config['prefix'] ?? 'native_csrf:');
        $tokenHash = hash('sha256', $token);

        return $prefix . $sessionId . ':' . $tokenHash;
    }
}
