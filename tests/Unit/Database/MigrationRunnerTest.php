<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Mugiew\StarterKit\Database\Migrations\MigrationRunner;
use Mugiew\StarterKit\Database\Seeders\SeederContract;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
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
    }

    #[Test]
    public function migrate_runs_pending_migrations_and_tracks_history(): void
    {
        $runner = new MigrationRunner(
            $this->capsule,
            self::basePath() . '/database/migrations'
        );

        $result = $runner->migrate();

        self::assertCount(2, $result['migrated']);
        self::assertTrue($this->capsule->schema()->hasTable('migrations'));
        self::assertTrue($this->capsule->schema()->hasTable('users'));
        self::assertTrue($this->capsule->schema()->hasTable('profiles'));

        $secondPass = $runner->migrate();
        self::assertSame([], $secondPass['migrated']);
    }

    #[Test]
    public function migrate_fresh_with_seed_rebuilds_schema_and_runs_seeder(): void
    {
        $runner = new MigrationRunner(
            $this->capsule,
            self::basePath() . '/database/migrations',
            new class implements SeederContract {
                public function run(Capsule $capsule): void
                {
                    $capsule->table('users')->insert([
                        'username' => 'seeded_admin',
                        'name' => 'Seeded Admin',
                        'email' => 'seeded@example.com',
                        'password' => password_hash('Password123!', PASSWORD_DEFAULT),
                        'role' => 'admin',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        );

        $runner->migrate();
        $result = $runner->fresh(seed: true);

        self::assertTrue($result['seeded']);
        self::assertCount(2, $result['migrated']);
        self::assertSame(1, (int) $this->capsule->table('users')->count());
    }

    private static function basePath(): string
    {
        return dirname(__DIR__, 3);
    }
}
