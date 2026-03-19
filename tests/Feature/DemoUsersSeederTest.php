<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_users_seeder_creates_the_configured_staff_team(): void
    {
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
    }
}
