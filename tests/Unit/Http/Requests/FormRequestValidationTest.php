<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Http\Requests;

use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Http\Requests\Auth\RegisterRequest;
use Mugiew\StarterKit\Http\Requests\Dashboard\UpdateUserRequest;
use Mugiew\StarterKit\Http\Requests\Validation\RequestValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormRequestValidationTest extends TestCase
{
    #[Test]
    public function register_request_returns_normalized_payload(): void
    {
        $request = new Request(
            method: 'POST',
            path: '/register',
            query: [],
            body: [
                'username' => 'JohnDoe',
                'name' => ' John Doe ',
                'email' => 'JOHN@EXAMPLE.COM',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            server: [],
            cookies: [],
            files: [],
            headers: [],
        );

        $validated = RegisterRequest::fromRequest($request)->validated();

        self::assertSame('johndoe', $validated['username']);
        self::assertSame('John Doe', $validated['name']);
        self::assertSame('john@example.com', $validated['email']);
    }

    #[Test]
    public function update_request_accepts_empty_password_for_partial_update(): void
    {
        $request = new Request(
            method: 'POST',
            path: '/dashboard/users/update',
            query: [],
            body: [
                'user_id' => '10',
                'username' => 'newusername',
                'name' => 'New Name',
                'email' => 'new@example.com',
                'password' => '',
                'password_confirmation' => '',
            ],
            server: [],
            cookies: [],
            files: [],
            headers: [],
        );

        $validated = UpdateUserRequest::fromRequest($request)->validated();

        self::assertSame(10, $validated['user_id']);
        self::assertNull($validated['password']);
    }

    #[Test]
    public function it_throws_validation_exception_for_invalid_payload(): void
    {
        $request = new Request(
            method: 'POST',
            path: '/register',
            query: [],
            body: [
                'username' => 'ab',
                'name' => 'A',
                'email' => 'invalid-email',
                'password' => '123',
                'password_confirmation' => '1234',
            ],
            server: [],
            cookies: [],
            files: [],
            headers: [],
        );

        try {
            RegisterRequest::fromRequest($request);
            self::fail('Expected RequestValidationException to be thrown.');
        } catch (RequestValidationException $exception) {
            self::assertSame('/register', $exception->redirectPath());
            self::assertNotSame('', $exception->getMessage());
        }
    }
}
