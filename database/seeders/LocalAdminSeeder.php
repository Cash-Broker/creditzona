<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class LocalAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => 'local-admin@creditzona.test'],
            [
                'name' => 'Local Admin',
                'password' => Hash::make($this->getRequiredPassword()),
                'role' => User::ROLE_ADMIN,
            ],
        );
    }

    private function getRequiredPassword(): string
    {
        $password = env('LOCAL_ADMIN_PASSWORD');

        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException('Missing required env variable LOCAL_ADMIN_PASSWORD for LocalAdminSeeder.');
        }

        return $password;
    }
}
