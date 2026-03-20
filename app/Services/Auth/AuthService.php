<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Auth;

use Mugiew\StarterKit\Models\UserRepository;

final class AuthService
{
    private const SESSION_KEY = 'auth_user_id';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $resolvedUser = null;
    private bool $userResolved = false;

    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function id(): ?int
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        $id = (int) $_SESSION[self::SESSION_KEY];

        return $id > 0 ? $id : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        if ($this->userResolved) {
            return $this->resolvedUser;
        }

        $id = $this->id();

        if ($id === null) {
            $this->userResolved = true;
            return null;
        }

        $this->resolvedUser = $this->users->findById($id);
        $this->userResolved = true;

        if ($this->resolvedUser === null) {
            unset($_SESSION[self::SESSION_KEY]);
        }

        return $this->resolvedUser;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function register(string $username, string $name, string $email, string $password): ?array
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $id = $this->users->create(strtolower($username), ucwords($name), strtolower($email), $hashedPassword);

        if ($id === null) {
            return null;
        }

        return $this->users->findById($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail(strtolower($email));

        if ($user === null) {
            return null;
        }

        if (!password_verify($password, (string) $user['password'])) {
            return null;
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $user
     */
    public function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        $this->resolvedUser = $user;
        $this->userResolved = true;
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
        $this->resolvedUser = null;
        $this->userResolved = true;
    }
}
