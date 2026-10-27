<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@sistem.test'],
            [
                'name' => 'Admin Kejuaraan',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'operator@sistem.test'],
            [
                'name' => 'Operator 1',
                'password' => Hash::make('password'),
                'role' => 'operator',
            ],
        );
    }
}
