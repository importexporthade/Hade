<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database for local development.
     *
     * The manager account is admin@hade.test with the factory password "password". In
     * production create the owner's account with `php artisan hade:create-admin` instead.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Hade',
            'email' => 'admin@hade.test',
        ]);

        $this->call(StoreSeeder::class);
    }
}
