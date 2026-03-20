<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Http\Middlewares;

use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Http\Middlewares\RateLimiterMiddleware;
use Mugiew\StarterKit\Tests\Support\InMemoryRedisClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RateLimiterMiddlewareTest extends TestCase
{
    private RateLimiterMiddleware $middleware;

    public static function setUpBeforeClass(): void
    {
        require_once self::basePath() . '/helper/functions.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];

        $this->middleware = new RateLimiterMiddleware(
            new InMemoryRedisClient(),
            [
                'enabled' => true,
                'prefix' => 'tests_rate_limit:',
                'login' => [
                    'max_attempts' => 2,
                    'decay_seconds' => 60,
                ],
                'register' => [
                    'max_attempts' => 2,
                    'decay_seconds' => 60,
                ],
            ]
        );
    }

    #[Test]
    public function login_route_rate_limits_by_email_identifier(): void
    {
        $request = $this->request('/login', [
            'email' => 'tester@example.com',
            'password' => 'Password123!',
        ]);

        $executed = 0;
        $next = static function () use (&$executed): Response {
            $executed++;
            return Response::html('ok', 204);
        };

        $first = $this->middleware->process($request, $next);
        $second = $this->middleware->process($request, $next);
        $third = $this->middleware->process($request, $next);

        self::assertSame(204, $first->status());
        self::assertSame(204, $second->status());
        self::assertSame(302, $third->status());
        self::assertSame('/login', $third->headers()['Location'] ?? null);
        self::assertSame(2, $executed);
    }

    #[Test]
    public function register_route_rate_limits_by_combined_email_and_username(): void
    {
        $request = $this->request('/register', [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'name' => 'New User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $executed = 0;
        $next = static function () use (&$executed): Response {
            $executed++;
            return Response::html('ok', 204);
        };

        $this->middleware->process($request, $next);
        $this->middleware->process($request, $next);
        $third = $this->middleware->process($request, $next);

        self::assertSame(302, $third->status());
        self::assertSame('/register', $third->headers()['Location'] ?? null);
        self::assertSame(2, $executed);

        $differentIdentity = $this->request('/register', [
            'email' => 'another@example.com',
            'username' => 'another',
            'name' => 'Another User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $allowed = $this->middleware->process($differentIdentity, $next);

        self::assertSame(204, $allowed->status());
        self::assertSame(3, $executed);
    }

    #[Test]
    public function successful_auth_response_resets_the_rate_limit_bucket(): void
    {
        $request = $this->request('/login', [
            'email' => 'reset@example.com',
            'password' => 'Password123!',
        ]);

        $failedResponse = static fn (): Response => Response::redirect('/login');
        $successResponse = static fn (): Response => Response::redirect('/dashboard');

        $this->middleware->process($request, $failedResponse);
        $this->middleware->process($request, $successResponse);

        $afterResetFirst = $this->middleware->process($request, $failedResponse);
        $afterResetSecond = $this->middleware->process($request, $failedResponse);
        $afterResetThird = $this->middleware->process($request, $failedResponse);

        self::assertSame(302, $afterResetFirst->status());
        self::assertSame('/login', $afterResetFirst->headers()['Location'] ?? null);
        self::assertSame(302, $afterResetSecond->status());
        self::assertSame('/login', $afterResetSecond->headers()['Location'] ?? null);
        self::assertSame(302, $afterResetThird->status());
        self::assertSame('/login', $afterResetThird->headers()['Location'] ?? null);
        self::assertStringContainsString(
            'Too many attempts.',
            (string) ($_SESSION['_flash']['error'] ?? '')
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $path, array $body): Request
    {
        return new Request(
            method: 'POST',
            path: $path,
            query: [],
            body: $body,
            server: [],
            cookies: [],
            files: [],
            headers: [],
        );
    }

    private static function basePath(): string
    {
        return dirname(__DIR__, 4);
    }
}
