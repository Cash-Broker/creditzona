<?php

namespace Tests\Feature;

use App\Livewire\AdminLeadAlerts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLeadAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lead_alerts_component_can_refresh_its_state(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminLeadAlerts::class)
            ->assertSeeHtml('wire:poll.5s="refreshState"')
            ->call('refreshState')
            ->assertSet('attachedCount', 0)
            ->assertSet('returnedToMeCount', 0);
    }
}
