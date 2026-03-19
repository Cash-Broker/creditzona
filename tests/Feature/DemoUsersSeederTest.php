<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_users_seeder_creates_the_configured_staff_team(): void
    {
        $this->setDemoUserPasswords();

        $this->seed(DemoUsersSeeder::class);

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@creditzona.bg',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Рената',
            'email' => 'renata@creditzona.bg',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Анна',
            'email' => 'anna@creditzona.bg',
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Елена',
            'email' => 'elena@creditzona.bg',
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Красимира',
            'email' => 'krasimira@creditzona.bg',
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Искра',
            'email' => 'iskra@creditzona.bg',
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->assertTrue(Hash::check(
            'RenataCreditZona!1',
            User::query()->where('email', 'renata@creditzona.bg')->value('password'),
        ));
    }

    private function setDemoUserPasswords(): void
    {
        $passwords = [
            'DEMO_RENATA_PASSWORD' => 'RenataCreditZona!1',
            'DEMO_ANNA_PASSWORD' => 'AnnaCreditZona!2',
            'DEMO_ELENA_PASSWORD' => 'ElenaCreditZona!3',
            'DEMO_KRASIMIRA_PASSWORD' => 'KrasimiraCreditZona!4',
            'DEMO_ISKRA_PASSWORD' => 'IskraCreditZona!5',
        ];

        foreach ($passwords as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
