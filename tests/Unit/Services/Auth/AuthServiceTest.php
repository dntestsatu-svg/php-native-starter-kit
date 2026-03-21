<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Services\Auth;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Mugiew\StarterKit\Models\User;
use Mugiew\StarterKit\Services\Auth\AuthService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private Capsule $capsule;
    private AuthService $auth;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->startSession();
        $this->auth = new AuthService(new User());
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        parent::tearDown();
    }

    #[Test]
    public function register_normalizes_user_payload_and_hashes_password(): void
    {
        $registered = $this->auth->register(
            'JohnDoe',
            'john doe',
            'JOHN@EXAMPLE.COM',
            'Password123!'
        );

        self::assertIsArray($registered);
        self::assertSame('johndoe', $registered['username']);
        self::assertSame('John Doe', $registered['name']);
        self::assertSame('john@example.com', $registered['email']);
        self::assertArrayNotHasKey('password', $registered);

        /** @var User|null $storedUser */
        $storedUser = User::query()->where('email', 'john@example.com')->first();

        self::assertInstanceOf(User::class, $storedUser);
        self::assertTrue(
            password_verify('Password123!', (string) $storedUser->getAttribute('password'))
        );
    }

    #[Test]
    public function register_returns_null_when_username_or_email_already_exists(): void
    {
        $first = $this->auth->register(
            'existing',
            'Existing User',
            'existing@example.com',
            'Password123!'
        );

        self::assertIsArray($first);

        $duplicateEmail = $this->auth->register(
            'newusername',
            'New Username',
            'existing@example.com',
            'Password123!'
        );
        $duplicateUsername = $this->auth->register(
            'existing',
            'Another Name',
            'another@example.com',
            'Password123!'
        );

        self::assertNull($duplicateEmail);
        self::assertNull($duplicateUsername);
    }

    #[Test]
    public function attempt_validates_credentials(): void
    {
        $registered = $this->auth->register(
            'tester',
            'Tester Name',
            'tester@example.com',
            'Password123!'
        );

        self::assertIsArray($registered);

        $invalidPassword = $this->auth->attempt('tester@example.com', 'wrong-password');
        $missingUser = $this->auth->attempt('missing@example.com', 'Password123!');
        $valid = $this->auth->attempt('TESTER@EXAMPLE.COM', 'Password123!');

        self::assertNull($invalidPassword);
        self::assertNull($missingUser);
        self::assertIsArray($valid);
        self::assertSame('tester@example.com', $valid['email']);
    }

    #[Test]
    public function login_and_logout_manage_session_state(): void
    {
        $registered = $this->auth->register(
            'sessionuser',
            'Session User',
            'session@example.com',
            'Password123!'
        );

        self::assertIsArray($registered);

        $this->auth->login($registered);

        self::assertTrue($this->auth->check());
        self::assertSame((int) $registered['id'], $this->auth->id());
        self::assertIsArray($this->auth->user());

        $this->auth->logout();

        self::assertFalse($this->auth->check());
        self::assertNull($this->auth->id());
        self::assertNull($this->auth->user());
    }

    #[Test]
    public function user_call_clears_stale_session_identifier_when_user_is_missing(): void
    {
        $_SESSION['auth_user_id'] = 999_999;

        $resolvedUser = $this->auth->user();

        self::assertNull($resolvedUser);
        self::assertArrayNotHasKey('auth_user_id', $_SESSION);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('auth-test-' . bin2hex(random_bytes(6)));
        session_start();
        $_SESSION = [];
    }
}
