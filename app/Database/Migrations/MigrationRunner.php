<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Mugiew\StarterKit\Database\Seeders\SeederContract;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(
        private readonly Capsule $capsule,
        private readonly string $migrationsPath,
        private readonly ?SeederContract $seeder = null,
    ) {}

    /**
     * @return array{batch: int, migrated: list<string>}
     */
    public function migrate(): array
    {
        $this->ensureMigrationTable();

        $files = $this->migrationFiles();
        $executed = $this->executedMigrations();
        $pending = [];

        foreach ($files as $name => $filePath) {
            if (isset($executed[$name])) {
                continue;
            }

            $pending[$name] = $filePath;
        }

        if ($pending === []) {
            return ['batch' => $this->currentBatch(), 'migrated' => []];
        }

        $batch = $this->currentBatch() + 1;
        $migrated = [];
        $schema = $this->capsule->schema();

        foreach ($pending as $name => $filePath) {
            $migration = $this->loadMigration($filePath);

            $migration->up($schema, $this->capsule);

            $this->capsule->table('migrations')->insert([
                'migration' => $name,
                'batch' => $batch,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $migrated[] = $name;
        }

        return ['batch' => $batch, 'migrated' => $migrated];
    }

    /**
     * @return array{batch: int, migrated: list<string>, seeded: bool}
     */
    public function fresh(bool $seed = false): array
    {
        $this->dropAllTables();
        $result = $this->migrate();

        if ($seed) {
            $this->seed();
        }

        return [
            'batch' => $result['batch'],
            'migrated' => $result['migrated'],
            'seeded' => $seed,
        ];
    }

    public function seed(): void
    {
        if ($this->seeder === null) {
            throw new RuntimeException('Seeder is not configured.');
        }

        $this->seeder->run($this->capsule);
    }

    /**
     * @return array<string, string>
     */
    private function migrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.php');

        if ($files === false) {
            return [];
        }

        sort($files, SORT_NATURAL);

        $mapped = [];

        foreach ($files as $filePath) {
            $name = pathinfo($filePath, PATHINFO_FILENAME);

            if ($name === '') {
                continue;
            }

            $mapped[$name] = $filePath;
        }

        return $mapped;
    }

    /**
     * @return array<string, true>
     */
    private function executedMigrations(): array
    {
        /** @var list<object{migration: string}> $records */
        $records = $this->capsule->table('migrations')->select(['migration'])->get()->all();
        $executed = [];

        foreach ($records as $record) {
            if (!isset($record->migration) || !is_string($record->migration)) {
                continue;
            }

            $executed[$record->migration] = true;
        }

        return $executed;
    }

    private function ensureMigrationTable(): void
    {
        $schema = $this->capsule->schema();

        if ($schema->hasTable('migrations')) {
            return;
        }

        $schema->create('migrations', static function ($table): void {
            $table->id();
            $table->string('migration')->unique();
            $table->unsignedInteger('batch');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function currentBatch(): int
    {
        /** @var object{batch: int}|null $result */
        $result = $this->capsule->table('migrations')->selectRaw('MAX(batch) as batch')->first();

        if ($result === null || !isset($result->batch)) {
            return 0;
        }

        return (int) $result->batch;
    }

    private function dropAllTables(): void
    {
        $schema = $this->capsule->schema();
        $schema->dropAllTables();
    }

    private function loadMigration(string $filePath): MigrationContract
    {
        $migration = require $filePath;

        if ($migration instanceof MigrationContract) {
            return $migration;
        }

        throw new RuntimeException(sprintf(
            'Invalid migration file "%s". It must return %s.',
            $filePath,
            MigrationContract::class
        ));
    }
}
