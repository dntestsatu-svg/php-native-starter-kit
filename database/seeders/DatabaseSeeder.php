<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Database\Seeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Mugiew\StarterKit\Models\Profile;
use Mugiew\StarterKit\Models\User;

final class DatabaseSeeder implements SeederContract
{
    public function run(Capsule $capsule): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'name' => 'Administrator',
                'password' => password_hash('Password123!', PASSWORD_DEFAULT),
                'role' => 'admin',
            ]
        );

        Profile::query()->firstOrCreate(
            ['user_id' => (int) $user->id],
            [
                'bio' => 'Starter administrator account.',
                'website' => null,
            ]
        );
    }
}
