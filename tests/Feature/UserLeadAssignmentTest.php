<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLeadAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_helpers_identify_admin_role(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isOperator());
    }

    public function test_user_helpers_identify_operator_role(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->assertTrue($user->isOperator());
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_leads_relationship_returns_assigned_leads(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $assignedLead = Lead::query()->create($this->leadData([
            'phone' => '0888000001',
            'assigned_user_id' => $operator->id,
        ]));

        Lead::query()->create($this->leadData([
            'phone' => '0888000002',
            'assigned_user_id' => $otherOperator->id,
        ]));

        $this->assertCount(1, $operator->leads);
        $this->assertTrue($operator->leads->first()->is($assignedLead));
    }

    public function test_lead_assigned_user_relationship_returns_user(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->assertTrue($lead->assignedUser->is($operator));
    }

    public function test_deleting_assigned_user_nulls_lead_assignment(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $operator->delete();
        $lead->refresh();

        $this->assertNull($lead->assigned_user_id);
        $this->assertNull($lead->assignedUser);
    }

    /**
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => 'consumer',
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'status' => 'new',
            'assigned_user_id' => null,
        ], $overrides);
    }
}
