<?php

namespace Tests\Feature;

use App\Livewire\AdminLeadAssignmentStatusToggle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLeadAssignmentStatusToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_user_can_toggle_lead_assignment_availability_from_admin_panel(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
            'is_available_for_lead_assignment' => true,
        ]);

        $this->actingAs($operator);

        Livewire::test(AdminLeadAssignmentStatusToggle::class)
            ->assertSet('isAvailableForLeadAssignment', true)
            ->assertSet('canToggleOwnAvailability', true)
            ->call('toggleAvailability')
            ->assertSet('isAvailableForLeadAssignment', false)
            ->call('toggleAvailability')
            ->assertSet('isAvailableForLeadAssignment', true);

        $this->assertTrue($operator->fresh()->isAvailableForLeadAssignment());
    }

    public function test_admin_can_see_primary_operator_online_and_offline_statuses(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'name' => 'Анна',
            'email' => 'anna@creditzona.test',
            'is_available_for_lead_assignment' => true,
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'name' => 'Елена',
            'email' => 'elena@creditzona.test',
            'is_available_for_lead_assignment' => false,
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminLeadAssignmentStatusToggle::class)
            ->assertSet('canToggleOwnAvailability', false)
            ->assertSet('canViewTeamAvailability', true)
            ->assertSee('Анна')
            ->assertSee('Елена')
            ->assertSee('Онлайн')
            ->assertSee('Офлайн');
    }
}
