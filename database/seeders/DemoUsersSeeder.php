<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->whereIn('email', [
                'admin@creditzona.bg',
                'operator1@creditzona.bg',
                'operator2@creditzona.bg',
            ])
            ->delete();

        collect([
            ['name' => 'Рената', 'email' => 'renata@creditzona.bg', 'password_env' => 'DEMO_RENATA_PASSWORD'],
            ['name' => 'Анна', 'email' => 'anna@creditzona.bg', 'password_env' => 'DEMO_ANNA_PASSWORD'],
            ['name' => 'Елена', 'email' => 'elena@creditzona.bg', 'password_env' => 'DEMO_ELENA_PASSWORD'],
            ['name' => 'Красимира', 'email' => 'krasimira@creditzona.bg', 'password_env' => 'DEMO_KRASIMIRA_PASSWORD'],
            ['name' => 'Искра', 'email' => 'iskra@creditzona.bg', 'password_env' => 'DEMO_ISKRA_PASSWORD'],
        ])->each(function (array $user): void {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($this->getRequiredPassword($user['password_env'])),
                    'role' => $user['email'] === 'renata@creditzona.bg'
                        ? User::ROLE_ADMIN
                        : User::ROLE_OPERATOR,
                ],
            );
        });
    }

    private function getRequiredPassword(string $envKey): string
    {
        $password = env($envKey);

        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException("Missing required demo user password env: {$envKey}");
        }

        return $password;
    }
}
