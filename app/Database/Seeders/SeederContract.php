<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Database\Seeders;

use Illuminate\Database\Capsule\Manager as Capsule;

interface SeederContract
{
    public function run(Capsule $capsule): void;
}
