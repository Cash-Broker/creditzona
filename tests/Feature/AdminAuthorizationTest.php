<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\User;
use App\Policies\LeadPolicy;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_staff_roles_can_access_admin_panel_and_lead_records(): void
    {
        $renata = User::factory()->make([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $primaryOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $additionalOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $unrelatedOperator = User::factory()->make([
            'role' => User::ROLE_OPERATOR,
            'email' => 'iskra@creditzona.test',
        ]);

        $nonStaff = User::factory()->make([
            'role' => 'customer',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $primaryOperator->id,
            'additional_user_id' => $additionalOperator->id,
        ]));
        $policy = new LeadPolicy;
        $adminPanel = (new Panel)->id('admin');

        $this->assertTrue($renata->canAccessPanel($adminPanel));
        $this->assertTrue($primaryOperator->canAccessPanel($adminPanel));
        $this->assertFalse($nonStaff->canAccessPanel($adminPanel));

        $this->assertTrue($policy->viewAny($renata));
        $this->assertTrue($policy->view($primaryOperator, $lead));
        $this->assertTrue($policy->view($additionalOperator, $lead));
        $this->assertTrue($policy->update($renata, $lead));
        $this->assertFalse($policy->view($unrelatedOperator, $lead));

        $this->assertFalse($policy->viewAny($nonStaff));
        $this->assertFalse($policy->view($nonStaff, $lead));
        $this->assertFalse($policy->update($nonStaff, $lead));
    }

    public function test_lead_resource_query_is_scoped_to_primary_and_additional_operator_assignments(): void
    {
        $renata = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $iskra = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'iskra@creditzona.test',
        ]);

        $assignedLead = Lead::query()->create($this->leadData([
            'phone' => '0888000001',
            'assigned_user_id' => $anna->id,
        ]));

        $collaboratingLead = Lead::query()->create($this->leadData([
            'phone' => '0888000002',
            'assigned_user_id' => $elena->id,
            'additional_user_id' => $anna->id,
        ]));

        $unrelatedLead = Lead::query()->create($this->leadData([
            'phone' => '0888000003',
            'assigned_user_id' => $iskra->id,
        ]));

        $this->actingAs($anna);

        $operatorVisibleLeadIds = LeadResource::getEloquentQuery()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([
            $assignedLead->id,
            $collaboratingLead->id,
        ], $operatorVisibleLeadIds);

        $this->actingAs($renata);

        $adminVisibleLeadIds = LeadResource::getEloquentQuery()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([
            $assignedLead->id,
            $collaboratingLead->id,
            $unrelatedLead->id,
        ], $adminVisibleLeadIds);
    }

    /**
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => 'consumer',
            'first_name' => 'Ivan',
            'last_name' => 'Ivanov',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Plovdiv',
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'status' => 'new',
            'assigned_user_id' => null,
            'additional_user_id' => null,
        ], $overrides);
    }
}
