<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadGuarantor;
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
            'email' => 'anna@creditzona.test',
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

    public function test_create_lead_stores_additional_fields_and_guarantors(): void
    {
        $lead = app(LeadService::class)->createLead($this->leadData([
            'middle_name' => 'Петров',
            'workplace' => 'Тест ООД',
            'job_title' => 'Търговски представител',
            'salary' => 3200,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 2,
            'salary_bank' => 'Банка ДСК',
            'guarantors' => [
                [
                    'first_name' => 'Мария',
                    'last_name' => 'Георгиева',
                    'phone' => '0888111222',
                    'status' => LeadGuarantor::STATUS_SUITABLE,
                ],
                [
                    'first_name' => 'Николай',
                    'last_name' => 'Димитров',
                    'phone' => null,
                    'status' => LeadGuarantor::STATUS_DECLINED,
                ],
            ],
        ]));

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'middle_name' => 'Петров',
            'workplace' => 'Тест ООД',
            'job_title' => 'Търговски представител',
            'salary' => 3200,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 2,
            'salary_bank' => 'Банка ДСК',
        ]);

        $this->assertCount(2, $lead->guarantors);

        $this->assertDatabaseHas('lead_guarantors', [
            'lead_id' => $lead->id,
            'first_name' => 'Мария',
            'last_name' => 'Георгиева',
            'phone' => '0888111222',
            'status' => LeadGuarantor::STATUS_SUITABLE,
        ]);

        $this->assertDatabaseHas('lead_guarantors', [
            'lead_id' => $lead->id,
            'first_name' => 'Николай',
            'last_name' => 'Димитров',
            'phone' => null,
            'status' => LeadGuarantor::STATUS_DECLINED,
        ]);
    }

    public function test_create_lead_reuses_historical_assigned_user_for_same_phone(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        $historicalOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
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

    public function test_create_lead_uses_fallback_primary_assignment_pool_when_historical_assignment_is_missing(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'renata@creditzona.test',
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

        $this->assertSame($anna->id, $lead->assigned_user_id);

        Carbon::setTestNow();
    }

    public function test_create_lead_does_not_reuse_historical_assignment_outside_primary_assignment_pool(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $renata = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'renata@creditzona.test',
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
            'assigned_user_id' => $renata->id,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData());

        $this->assertSame($anna->id, $lead->assigned_user_id);

        Carbon::setTestNow();
    }

    public function test_create_lead_rotates_fallback_assignment_with_round_robin_only_between_the_three_primary_operators(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $krasimira = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'krasimira@creditzona.test',
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'renata@creditzona.test',
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'iskra@creditzona.test',
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

        $this->assertSame($anna->id, $firstLead->assigned_user_id);
        $this->assertSame($elena->id, $secondLead->assigned_user_id);
        $this->assertSame($krasimira->id, $thirdLead->assigned_user_id);
        $this->assertSame($anna->id, $fourthLead->assigned_user_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => 'consumer',
            'first_name' => 'Иван',
            'middle_name' => null,
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'workplace' => null,
            'job_title' => null,
            'salary' => null,
            'marital_status' => null,
            'children_under_18' => null,
            'salary_bank' => null,
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'guarantors' => [],
            'additional_user_id' => null,
        ], $overrides);
    }
}
