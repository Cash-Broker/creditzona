<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@creditzona.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'operator1@creditzona.test'],
            [
                'name' => 'Operator One',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERATOR,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'operator2@creditzona.test'],
            [
                'name' => 'Operator Two',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERATOR,
            ],
        );
    }
}
