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

    public function test_user_helpers_identify_assignment_eligibility(): void
    {
        $primaryEligibleOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $primaryEligibleBgOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.bg',
        ]);

        $manualOnlyOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'iskra@creditzona.test',
        ]);

        $manualOnlyBgOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'iskra@creditzona.bg',
        ]);

        $manualOnlyAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $manualOnlyBgAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.bg',
        ]);

        $ineligibleOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'maria@creditzona.test',
        ]);

        $this->assertTrue($primaryEligibleOperator->canBeLeadPrimaryAssignee());
        $this->assertTrue($primaryEligibleOperator->canBeLeadAdditionalAssignee());
        $this->assertTrue($primaryEligibleBgOperator->canBeLeadPrimaryAssignee());
        $this->assertTrue($primaryEligibleBgOperator->canBeLeadAdditionalAssignee());

        $this->assertFalse($manualOnlyOperator->canBeLeadPrimaryAssignee());
        $this->assertTrue($manualOnlyOperator->canBeLeadAdditionalAssignee());
        $this->assertFalse($manualOnlyBgOperator->canBeLeadPrimaryAssignee());
        $this->assertTrue($manualOnlyBgOperator->canBeLeadAdditionalAssignee());

        $this->assertFalse($manualOnlyAdmin->canBeLeadPrimaryAssignee());
        $this->assertTrue($manualOnlyAdmin->canBeLeadAdditionalAssignee());
        $this->assertFalse($manualOnlyBgAdmin->canBeLeadPrimaryAssignee());
        $this->assertTrue($manualOnlyBgAdmin->canBeLeadAdditionalAssignee());

        $this->assertFalse($ineligibleOperator->canBeLeadPrimaryAssignee());
        $this->assertFalse($ineligibleOperator->canBeLeadAdditionalAssignee());

        $this->assertEqualsCanonicalizing([
            'anna@creditzona.test',
            'anna@creditzona.bg',
            'iskra@creditzona.test',
            'iskra@creditzona.bg',
            'renata@creditzona.test',
            'renata@creditzona.bg',
        ], User::query()
            ->whereIn('email', [
                'anna@creditzona.test',
                'anna@creditzona.bg',
                'iskra@creditzona.test',
                'iskra@creditzona.bg',
                'renata@creditzona.test',
                'renata@creditzona.bg',
                'maria@creditzona.test',
            ])
            ->eligibleForLeadAdditionalAssignment()
            ->pluck('email')
            ->all());
    }

    public function test_primary_assignment_scope_excludes_offline_operators(): void
    {
        $onlineOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
            'is_available_for_lead_assignment' => true,
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
            'is_available_for_lead_assignment' => false,
        ]);

        $this->assertSame(
            [$onlineOperator->email],
            User::query()
                ->eligibleForLeadPrimaryAssignment()
                ->pluck('email')
                ->all(),
        );
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

    public function test_user_additional_leads_relationship_returns_collaborating_leads(): void
    {
        $additionalOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $collaboratingLead = Lead::query()->create($this->leadData([
            'phone' => '0888000003',
            'additional_user_id' => $additionalOperator->id,
        ]));

        Lead::query()->create($this->leadData([
            'phone' => '0888000004',
            'additional_user_id' => $otherOperator->id,
        ]));

        $this->assertCount(1, $additionalOperator->additionalLeads);
        $this->assertTrue($additionalOperator->additionalLeads->first()->is($collaboratingLead));
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

    public function test_deleting_additional_user_nulls_lead_collaboration_assignment(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'krasimira@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'phone' => '0888000005',
            'additional_user_id' => $operator->id,
        ]));

        $operator->delete();
        $lead->refresh();

        $this->assertNull($lead->additional_user_id);
        $this->assertNull($lead->additionalUser);
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
            'additional_user_id' => null,
        ], $overrides);
    }
}
