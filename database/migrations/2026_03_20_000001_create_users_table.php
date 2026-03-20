<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Mugiew\StarterKit\Database\Migrations\MigrationContract;

return new class implements MigrationContract {
    public function up(Builder $schema, Capsule $capsule): void
    {
        if ($schema->hasTable('users')) {
            return;
        }

        $driver = $capsule->getConnection()->getDriverName();

        $schema->create('users', static function (Blueprint $table) use ($driver): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();

            if ($driver === 'mysql') {
                $table->enum('role', ['dev', 'superadmin', 'admin', 'user'])->default('user');
            } else {
                $table->string('role', 32)->default('user');
            }

            $table->timestamps();
        });
    }

    public function down(Builder $schema, Capsule $capsule): void
    {
        $schema->dropIfExists('users');
    }
};
