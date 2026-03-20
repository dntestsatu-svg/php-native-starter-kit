<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Middlewares;

use Mugiew\StarterKit\Core\MiddlewareInterface;
use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Predis\Client;
use Throwable;

final class RateLimiterMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly Client $redis,
        private readonly array $config = [],
    ) {}

    #[\Override]
    public function process(Request $request, callable $next): Response
    {
        if (!($this->config['enabled'] ?? true)) {
            return $next($request);
        }

        $policy = $this->policyFor($request);

        if ($policy === null) {
            return $next($request);
        }

        $key = $this->resolveKey($request, $policy['identity_fields'], $policy['key']);
        $maxAttempts = max(1, (int) $policy['max_attempts']);
        $decaySeconds = max(1, (int) $policy['decay_seconds']);

        if ($this->tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
            flash(
                'error',
                sprintf(
                    'Too many attempts. Please try again in %d seconds.',
                    $this->retryAfterSeconds($key, $decaySeconds)
                )
            );

            return Response::redirect($request->path());
        }

        $response = $next($request);

        if ($this->shouldResetAttempts($request, $response)) {
            $this->clear($key);
        }

        return $response;
    }

    /**
     * @return array{key: string, identity_fields: array<int, string>, max_attempts: int, decay_seconds: int}|null
     */
    private function policyFor(Request $request): ?array
    {
        if (!$request->isMethod('POST')) {
            return null;
        }

        return match ($request->path()) {
            '/login' => [
                'key' => 'login',
                'identity_fields' => ['email'],
                'max_attempts' => (int) (($this->config['login']['max_attempts'] ?? 5)),
                'decay_seconds' => (int) (($this->config['login']['decay_seconds'] ?? 60)),
            ],
            '/register' => [
                'key' => 'register',
                'identity_fields' => ['email', 'username'],
                'max_attempts' => (int) (($this->config['register']['max_attempts'] ?? 3)),
                'decay_seconds' => (int) (($this->config['register']['decay_seconds'] ?? 300)),
            ],
            default => null,
        };
    }

    /**
     * @param array<int, string> $fields
     */
    private function resolveKey(Request $request, array $fields, string $bucket): string
    {
        $parts = [];

        foreach ($fields as $field) {
            $normalized = $this->normalizeIdentifier($request->input($field));

            if ($normalized !== '') {
                $parts[] = sprintf('%s=%s', $field, $normalized);
            }
        }

        if ($parts === []) {
            $parts[] = 'fingerprint=' . $this->requestFingerprint($request);
        }

        $prefix = (string) ($this->config['prefix'] ?? 'native_rate_limit:');
        $hash = hash('sha256', implode('|', $parts));

        return sprintf('%s%s:%s', $prefix, $bucket, $hash);
    }

    private function normalizeIdentifier(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return strtolower(trim((string) $value));
    }

    private function requestFingerprint(Request $request): string
    {
        $sessionId = session_id();

        if ($sessionId !== '') {
            return 'session:' . $sessionId;
        }

        $userAgent = (string) $request->header('USER_AGENT', '');
        $acceptLanguage = (string) $request->header('ACCEPT_LANGUAGE', '');
        $payloadKeys = array_keys($request->body());

        return hash(
            'sha256',
            $request->path() . '|' . $userAgent . '|' . $acceptLanguage . '|' . implode(',', $payloadKeys)
        );
    }

    private function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        try {
            $attempts = (int) $this->redis->incr($key);

            if ($attempts === 1) {
                $this->redis->expire($key, $decaySeconds);
            }

            return $attempts > $maxAttempts;
        } catch (Throwable) {
            // Fail open to avoid authentication outages when Redis is unavailable.
            return false;
        }
    }

    private function retryAfterSeconds(string $key, int $fallbackSeconds): int
    {
        try {
            $ttl = (int) $this->redis->ttl($key);

            if ($ttl > 0) {
                return $ttl;
            }
        } catch (Throwable) {
            // Fall back to configured decay.
        }

        return $fallbackSeconds;
    }

    private function shouldResetAttempts(Request $request, Response $response): bool
    {
        if (!in_array($request->path(), ['/login', '/register'], true)) {
            return false;
        }

        if ($response->status() !== 302) {
            return false;
        }

        return ($response->headers()['Location'] ?? null) === '/dashboard';
    }

    private function clear(string $key): void
    {
        try {
            $this->redis->del([$key]);
        } catch (Throwable) {
            // Best effort cleanup only.
        }
    }
}
