<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Security;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Mugiew\StarterKit\Core\Application;
use Mugiew\StarterKit\Core\Container;
use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Core\Router;
use Mugiew\StarterKit\Core\View;
use Mugiew\StarterKit\Http\Middlewares\CsrfMiddleware;
use Mugiew\StarterKit\Models\User;
use Mugiew\StarterKit\Services\Auth\AuthService;
use Mugiew\StarterKit\Services\Security\CsrfManager;
use Mugiew\StarterKit\Tests\Support\InMemoryRedisClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SecurityHardeningTest extends TestCase
{
    private Capsule $capsule;
    private User $users;
    private mixed $previousApp;

    public static function setUpBeforeClass(): void
    {
        require_once self::basePath() . '/helper/functions.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousApp = $GLOBALS['app'] ?? null;
        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $this->capsule->schema()->create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->timestamps();
        });

        $this->users = new User();
    }

    protected function tearDown(): void
    {
        if ($this->previousApp === null) {
            unset($GLOBALS['app']);
        } else {
            $GLOBALS['app'] = $this->previousApp;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        parent::tearDown();
    }

    #[Test]
    public function login_is_not_vulnerable_to_basic_sql_injection_payloads(): void
    {
        $password = 'StrongPass123!';
        $this->users->newQuery()->create([
            'username' => 'victim',
            'name' => 'Victim User',
            'email' => 'victim@example.com',
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $auth = new AuthService($this->users);
        $injectionAttempt = $auth->attempt("victim@example.com' OR '1'='1", $password);
        $validAttempt = $auth->attempt('victim@example.com', $password);

        self::assertNull($injectionAttempt);
        self::assertIsArray($validAttempt);
        self::assertSame('victim@example.com', $validAttempt['email']);
    }

    #[Test]
    public function insert_payloads_with_sql_keywords_are_stored_as_data_not_executed(): void
    {
        $usernamePayload = "attacker'); DROP TABLE users; --";

        $created = $this->users->newQuery()->create([
            'username' => $usernamePayload,
            'name' => 'Payload User',
            'email' => 'payload@example.com',
            'password' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        self::assertNotNull($created->id);

        /** @var User|null $stored */
        $stored = $this->users->newQuery()->find((int) $created->id);
        self::assertInstanceOf(User::class, $stored);
        self::assertSame($usernamePayload, $stored->username);

        self::assertSame(1, (int) $this->users->newQuery()->count());
    }

    #[Test]
    public function csrf_middleware_accepts_valid_token_and_blocks_replay(): void
    {
        $this->assignSessionId();

        $csrf = $this->csrfManager();
        $middleware = $this->csrfMiddleware($csrf);
        $token = $csrf->issueToken();

        $request = new Request(
            method: 'POST',
            path: '/dashboard/users',
            query: [],
            body: ['_token' => $token],
            server: [],
            cookies: [],
            files: [],
            headers: [],
        );

        $nextCalled = false;
        $firstResponse = $middleware->process(
            $request,
            function () use (&$nextCalled): Response {
                $nextCalled = true;
                return Response::html('ok');
            }
        );

        self::assertTrue($nextCalled);
        self::assertSame(200, $firstResponse->status());

        $replayCalled = false;
        $replayResponse = $middleware->process(
            $request,
            function () use (&$replayCalled): Response {
                $replayCalled = true;
                return Response::html('ok');
            }
        );

        self::assertFalse($replayCalled);
        self::assertSame(419, $replayResponse->status());
    }

    #[Test]
    public function csrf_middleware_rejects_forged_token(): void
    {
        $this->assignSessionId();

        $middleware = $this->csrfMiddleware($this->csrfManager());
        $request = new Request(
            method: 'POST',
            path: '/dashboard/users',
            query: [],
            body: ['_token' => 'forged-token'],
            server: [],
            cookies: [],
            files: [],
            headers: [],
        );

        $nextCalled = false;
        $response = $middleware->process(
            $request,
            function () use (&$nextCalled): Response {
                $nextCalled = true;
                return Response::html('ok');
            }
        );

        self::assertFalse($nextCalled);
        self::assertSame(419, $response->status());
    }

    #[Test]
    public function dashboard_output_escapes_untrusted_values_to_prevent_xss(): void
    {
        $this->assignSessionId();

        $csrf = $this->csrfManager();
        $this->bootstrapAppWithCsrf($csrf);

        $payload = '<img src=x onerror=alert(1)>';
        $html = (new View(self::basePath() . '/app/Views'))->render('dashboard.index', [
            'title' => 'Dashboard',
            'user' => ['name' => $payload, 'email' => $payload],
            'users' => [
                ['id' => 1, 'username' => $payload, 'name' => $payload, 'email' => $payload],
            ],
            'error' => $payload,
            'success' => $payload,
        ]);

        self::assertStringNotContainsString($payload, $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    private function assignSessionId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('security_' . bin2hex(random_bytes(8)));
    }

    private function csrfManager(): CsrfManager
    {
        return new CsrfManager(
            new InMemoryRedisClient(),
            [
                'enabled' => true,
                'token_field' => '_token',
                'header' => 'X-CSRF-TOKEN',
                'ttl' => 600,
                'prefix' => 'tests_csrf:',
                'except' => [],
            ]
        );
    }

    private function csrfMiddleware(CsrfManager $csrf): CsrfMiddleware
    {
        return new CsrfMiddleware(
            $csrf,
            new View(self::basePath() . '/app/Views'),
            ['except' => []]
        );
    }

    private function bootstrapAppWithCsrf(CsrfManager $csrf): void
    {
        $container = new Container();
        $container->instance(CsrfManager::class, $csrf);

        $GLOBALS['app'] = new Application($container, new Router());
    }

    private static function basePath(): string
    {
        return dirname(__DIR__, 3);
    }
}
