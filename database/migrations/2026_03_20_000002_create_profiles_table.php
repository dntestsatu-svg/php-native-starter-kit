<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Mugiew\StarterKit\Database\Migrations\MigrationContract;

return new class implements MigrationContract {
    public function up(Builder $schema, Capsule $capsule): void
    {
        if ($schema->hasTable('profiles')) {
            return;
        }

        $schema->create('profiles', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    public function down(Builder $schema, Capsule $capsule): void
    {
        $schema->dropIfExists('profiles');
    }
};
