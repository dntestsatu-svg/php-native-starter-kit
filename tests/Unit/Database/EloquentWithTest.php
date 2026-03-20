<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Mugiew\StarterKit\Models\Profile;
use Mugiew\StarterKit\Models\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EloquentWithTest extends TestCase
{
    private Capsule $capsule;

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

        $schema = $this->capsule->schema();

        $schema->create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->timestamps();
        });

        $schema->create('profiles', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function eager_loading_with_solves_n_plus_one_for_user_profiles(): void
    {
        $alice = User::query()->create([
            'username' => 'alice',
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        $bob = User::query()->create([
            'username' => 'bob',
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        Profile::query()->create([
            'user_id' => (int) $alice->id,
            'bio' => 'Alice bio',
        ]);

        Profile::query()->create([
            'user_id' => (int) $bob->id,
            'bio' => 'Bob bio',
        ]);

        $connection = $this->capsule->getConnection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $users = User::query()->with(['profile'])->orderBy('id')->get();

        $bios = $users->map(static fn (User $user): ?string => $user->profile?->bio)->all();
        $queries = $connection->getQueryLog();

        self::assertSame(['Alice bio', 'Bob bio'], $bios);
        self::assertCount(2, $queries);
    }

    #[Test]
    public function user_model_with_relationship_returns_related_profile_payloads(): void
    {
        $user = User::query()->create([
            'username' => 'charlie',
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
            'password' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        Profile::query()->create([
            'user_id' => (int) $user->id,
            'bio' => 'Profile for Charlie',
            'website' => 'https://example.test',
        ]);

        $rows = User::query()
            ->with(['profile'])
            ->orderByDesc('id')
            ->get()
            ->map(static fn (User $record): array => $record->toArray())
            ->all();

        self::assertCount(1, $rows);
        self::assertSame('charlie@example.com', $rows[0]['email']);
        self::assertSame('Profile for Charlie', $rows[0]['profile']['bio'] ?? null);
    }
}
