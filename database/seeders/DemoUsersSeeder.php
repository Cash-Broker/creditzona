<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        User::query()
            ->whereIn('email', [
                'admin@creditzona.bg',
                'operator1@creditzona.bg',
                'operator2@creditzona.bg',
            ])
            ->delete();

        collect([
            ['name' => 'Рената', 'email' => 'renata@creditzona.bg'],
            ['name' => 'Анна', 'email' => 'anna@creditzona.bg'],
            ['name' => 'Елена', 'email' => 'elena@creditzona.bg'],
            ['name' => 'Красимира', 'email' => 'krasimira@creditzona.bg'],
            ['name' => 'Искра', 'email' => 'iskra@creditzona.bg'],
        ])->each(function (array $user) use ($password): void {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $password,
                    'role' => $user['email'] === 'renata@creditzona.bg'
                        ? User::ROLE_ADMIN
                        : User::ROLE_OPERATOR,
                ],
            );
        });
    }
}
