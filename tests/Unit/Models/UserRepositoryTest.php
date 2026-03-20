<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Models;

use Mugiew\StarterKit\Models\UserRepository;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->repository = new UserRepository($pdo);
        $this->repository->ensureTable();
    }

    #[Test]
    public function it_can_create_and_read_a_user(): void
    {
        $id = $this->repository->create(
            'johndoe',
            'John Doe',
            'john@example.com',
            password_hash('Password123!', PASSWORD_DEFAULT)
        );

        self::assertNotNull($id);

        $userById = $this->repository->findById((int) $id);
        $userByEmail = $this->repository->findByEmail('john@example.com');

        self::assertSame('johndoe', $userById['username']);
        self::assertSame('John Doe', $userById['name']);
        self::assertSame('john@example.com', $userById['email']);
        self::assertSame((int) $id, (int) $userByEmail['id']);
    }

    #[Test]
    public function it_can_update_an_existing_user(): void
    {
        $id = $this->repository->create(
            'janedoe',
            'Jane Doe',
            'jane@example.com',
            password_hash('Password123!', PASSWORD_DEFAULT)
        );

        self::assertNotNull($id);

        $updated = $this->repository->update(
            (int) $id,
            'janeupdated',
            'Jane Updated',
            'jane.updated@example.com',
            password_hash('NewPassword123!', PASSWORD_DEFAULT)
        );

        self::assertTrue($updated);

        $user = $this->repository->findById((int) $id);
        self::assertSame('janeupdated', $user['username']);
        self::assertSame('Jane Updated', $user['name']);
        self::assertSame('jane.updated@example.com', $user['email']);
    }

    #[Test]
    public function it_can_delete_a_user(): void
    {
        $id = $this->repository->create(
            'deletethis',
            'Delete This',
            'delete@example.com',
            password_hash('Password123!', PASSWORD_DEFAULT)
        );

        self::assertNotNull($id);

        $deleted = $this->repository->delete((int) $id);

        self::assertTrue($deleted);
        self::assertNull($this->repository->findById((int) $id));
    }

    #[Test]
    public function it_prevents_duplicate_username_and_email(): void
    {
        $this->repository->create(
            'duplicate',
            'Duplicate User',
            'duplicate@example.com',
            password_hash('Password123!', PASSWORD_DEFAULT)
        );

        $duplicateUsername = $this->repository->create(
            'duplicate',
            'Another User',
            'another@example.com',
            password_hash('Password123!', PASSWORD_DEFAULT)
        );

        $duplicateEmail = $this->repository->create(
            'anotheruser',
            'Another User',
            'duplicate@example.com',
            password_hash('Password123!', PASSWORD_DEFAULT)
        );

        self::assertNull($duplicateUsername);
        self::assertNull($duplicateEmail);
    }
}
