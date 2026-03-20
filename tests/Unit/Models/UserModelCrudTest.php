<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Models;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Mugiew\StarterKit\Models\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserModelCrudTest extends TestCase
{
    private Capsule $capsule;
    private User $users;

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

        $this->users = new User();
    }

    #[Test]
    public function it_can_create_and_read_a_user(): void
    {
        $created = $this->users->newQuery()->create([
            'username' => 'johndoe',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        self::assertNotNull($created->id);

        /** @var User|null $userById */
        $userById = $this->users->newQuery()->find((int) $created->id);
        /** @var User|null $userByEmail */
        $userByEmail = $this->users->newQuery()->where('email', 'john@example.com')->first();

        self::assertInstanceOf(User::class, $userById);
        self::assertInstanceOf(User::class, $userByEmail);
        self::assertSame('johndoe', $userById->username);
        self::assertSame('John Doe', $userById->name);
        self::assertSame('john@example.com', $userById->email);
        self::assertSame((int) $created->id, (int) $userByEmail->id);
    }

    #[Test]
    public function it_can_update_an_existing_user(): void
    {
        $created = $this->users->newQuery()->create([
            'username' => 'janedoe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        $updated = $created->fill([
            'username' => 'janeupdated',
            'name' => 'Jane Updated',
            'email' => 'jane.updated@example.com',
            'password' => password_hash('NewPassword123!', PASSWORD_DEFAULT),
        ])->save();

        self::assertTrue($updated);

        /** @var User|null $user */
        $user = $this->users->newQuery()->find((int) $created->id);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('janeupdated', $user->username);
        self::assertSame('Jane Updated', $user->name);
        self::assertSame('jane.updated@example.com', $user->email);
    }

    #[Test]
    public function it_can_delete_a_user(): void
    {
        $created = $this->users->newQuery()->create([
            'username' => 'deletethis',
            'name' => 'Delete This',
            'email' => 'delete@example.com',
            'password' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        $deleted = $created->delete();

        self::assertTrue($deleted);
        self::assertNull($this->users->newQuery()->find((int) $created->id));
    }

    #[Test]
    public function it_prevents_duplicate_username_and_email(): void
    {
        $this->users->newQuery()->create([
            'username' => 'duplicate',
            'name' => 'Duplicate User',
            'email' => 'duplicate@example.com',
            'password' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        $duplicateUsername = false;
        $duplicateEmail = false;

        try {
            $this->users->newQuery()->create([
                'username' => 'duplicate',
                'name' => 'Another User',
                'email' => 'another@example.com',
                'password' => password_hash('Password123!', PASSWORD_DEFAULT),
            ]);
        } catch (QueryException) {
            $duplicateUsername = true;
        }

        try {
            $this->users->newQuery()->create([
                'username' => 'anotheruser',
                'name' => 'Another User',
                'email' => 'duplicate@example.com',
                'password' => password_hash('Password123!', PASSWORD_DEFAULT),
            ]);
        } catch (QueryException) {
            $duplicateEmail = true;
        }

        self::assertTrue($duplicateUsername);
        self::assertTrue($duplicateEmail);
    }
}
