<?php

namespace Tests\Feature;

use App\Filament\Resources\LeadTraffic\LeadTrafficResource;
use App\Filament\Resources\LeadTraffic\Pages\ListLeadTraffic;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadTrafficAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_renata_can_view_lead_traffic(): void
    {
        $this->actingAs($this->renata());

        $this->assertTrue(LeadTrafficResource::canViewAny());
    }

    public function test_other_admin_cannot_view_lead_traffic(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'someone-else@creditzona.bg',
        ]);

        $this->actingAs($admin);

        $this->assertFalse(LeadTrafficResource::canViewAny());
    }

    public function test_operator_cannot_view_lead_traffic(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.bg',
        ]);

        $this->actingAs($operator);

        $this->assertFalse(LeadTrafficResource::canViewAny());
    }

    public function test_lead_traffic_page_shows_ip_and_user_agent_for_renata(): void
    {
        $lead = $this->leadWithTraffic();

        $this->actingAs($this->renata());

        Livewire::test(ListLeadTraffic::class)
            ->assertCanSeeTableRecords([$lead])
            ->assertSee('203.0.113.7')
            ->assertSee('Mozilla/5.0 (TestDevice)');
    }

    public function test_lead_traffic_page_excludes_old_leads_without_captured_ip(): void
    {
        $newLead = $this->leadWithTraffic();

        $oldLead = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Стара',
            'last_name' => 'Заявка',
            'phone' => '0888999000',
            'email' => 'old@example.com',
            'city' => 'София',
            'amount' => 8000,
            'status' => 'new',
            'ip_address' => null,
            'user_agent' => null,
        ]);

        $this->actingAs($this->renata());

        Livewire::test(ListLeadTraffic::class)
            ->assertCanSeeTableRecords([$newLead])
            ->assertCanNotSeeTableRecords([$oldLead]);
    }

    public function test_lead_traffic_page_is_forbidden_for_non_viewer(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.bg',
        ]);

        $this->actingAs($operator);

        $this->get(LeadTrafficResource::getUrl('index'))->assertForbidden();
    }

    private function renata(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.bg',
        ]);
    }

    private function leadWithTraffic(): Lead
    {
        return Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
            'ip_address' => '203.0.113.7',
            'user_agent' => 'Mozilla/5.0 (TestDevice)',
        ]);
    }
}
