<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Builder;

interface MigrationContract
{
    public function up(Builder $schema, Capsule $capsule): void;

    public function down(Builder $schema, Capsule $capsule): void;
}
