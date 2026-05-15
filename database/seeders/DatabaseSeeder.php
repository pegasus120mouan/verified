<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['login' => 'admin'],
            [
                'name' => 'Administrateur',
                'email' => 'admin@verif-ticket.test',
                'password' => '12345',
            ]
        );
    }
}
