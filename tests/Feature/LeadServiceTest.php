<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_lead_stores_assigned_operator_when_provided(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->assertSame($operator->id, $lead->assigned_user_id);
        $this->assertTrue($lead->assignedUser->is($operator));
    }

    public function test_create_lead_defaults_assignment_to_null(): void
    {
        $lead = app(LeadService::class)->createLead($this->leadData());

        $this->assertNull($lead->assigned_user_id);
        $this->assertNull($lead->assignedUser);
    }

    public function test_create_lead_reuses_historical_assigned_user_for_same_phone(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        $historicalOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        Lead::query()->insert([
            'credit_type' => 'consumer',
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0888123456',
            'email' => 'petar@example.com',
            'city' => 'София',
            'amount' => 12000,
            'status' => 'new',
            'assigned_user_id' => $historicalOperator->id,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData());

        $this->assertSame($historicalOperator->id, $lead->assigned_user_id);

        Carbon::setTestNow();
    }

    public function test_create_lead_uses_fallback_admin_or_operator_when_historical_assignment_missing(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        $fallbackAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        Lead::query()->insert([
            'credit_type' => 'consumer',
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0888123456',
            'email' => 'petar@example.com',
            'city' => 'София',
            'amount' => 12000,
            'status' => 'new',
            'assigned_user_id' => null,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData());

        $this->assertSame($fallbackAdmin->id, $lead->assigned_user_id);

        Carbon::setTestNow();
    }

    public function test_create_lead_rotates_fallback_assignment_with_round_robin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $operatorOne = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $operatorTwo = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $firstLead = app(LeadService::class)->createLead($this->leadData([
            'phone' => '0888000001',
        ]));

        $secondLead = app(LeadService::class)->createLead($this->leadData([
            'phone' => '0888000002',
        ]));

        $thirdLead = app(LeadService::class)->createLead($this->leadData([
            'phone' => '0888000003',
        ]));

        $fourthLead = app(LeadService::class)->createLead($this->leadData([
            'phone' => '0888000004',
        ]));

        $this->assertSame($admin->id, $firstLead->assigned_user_id);
        $this->assertSame($operatorOne->id, $secondLead->assigned_user_id);
        $this->assertSame($operatorTwo->id, $thirdLead->assigned_user_id);
        $this->assertSame($admin->id, $fourthLead->assigned_user_id);
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
        ], $overrides);
    }
}
