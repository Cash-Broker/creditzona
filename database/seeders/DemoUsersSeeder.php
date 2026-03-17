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
                'admin@creditzona.test',
                'operator1@creditzona.test',
                'operator2@creditzona.test',
            ])
            ->delete();

        collect([
            ['name' => 'Рената', 'email' => 'renata@creditzona.test'],
            ['name' => 'Анна', 'email' => 'anna@creditzona.test'],
            ['name' => 'Елена', 'email' => 'elena@creditzona.test'],
            ['name' => 'Красимира', 'email' => 'krasimira@creditzona.test'],
            ['name' => 'Искра', 'email' => 'iskra@creditzona.test'],
        ])->each(function (array $user) use ($password): void {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $password,
                    'role' => $user['email'] === 'renata@creditzona.test'
                        ? User::ROLE_ADMIN
                        : User::ROLE_OPERATOR,
                ],
            );
        });
    }
}
