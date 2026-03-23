<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use App\Services\LeadService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

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

    public function test_create_lead_stores_database_notification_for_assigned_operator(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $notification = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $operator->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($notification);
        $payload = json_decode((string) $notification->data, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Имате нова заявка към вас', $payload['title']);
        $this->assertStringContainsString($lead->first_name, $payload['body']);
    }

    public function test_send_additional_assignment_notification_stores_database_notification_for_selected_operator(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $primary = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $additional = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $primary->id,
            'additional_user_id' => $additional->id,
        ]));

        app(LeadService::class)->sendAdditionalAssignmentNotification(
            $lead->fresh(),
            null,
            $admin,
        );

        $notification = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $additional->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($notification);
        $payload = json_decode((string) $notification->data, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Имате нова заявка към вас', $payload['title']);
        $this->assertStringContainsString($admin->name, $payload['body']);
    }

    public function test_create_lead_defaults_assignment_to_null(): void
    {
        $lead = app(LeadService::class)->createLead($this->leadData());

        $this->assertNull($lead->assigned_user_id);
        $this->assertNull($lead->assignedUser);
    }

    public function test_create_lead_stores_private_privacy_consent_snapshot(): void
    {
        $lead = app(LeadService::class)->createLead($this->leadData([
            'privacy_consent' => true,
        ]));

        $this->assertTrue($lead->hasPrivacyConsent());
        $this->assertSame(Lead::getPrivacyConsentDocumentName(), $lead->privacy_consent_document_name);
        $this->assertNotNull($lead->privacy_consent_document_path);
        $this->assertStringStartsWith('lead-consents/', (string) $lead->privacy_consent_document_path);
        Storage::disk('local')->assertExists((string) $lead->privacy_consent_document_path);
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

    public function test_create_consumer_with_guarantor_lead_keeps_consumer_contact_fields_and_stores_guarantor(): void
    {
        $lead = app(LeadService::class)->createLead($this->leadData([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'guarantors' => [
                [
                    'first_name' => 'Мария',
                    'last_name' => 'Георгиева',
                    'phone' => '0888111222',
                    'status' => LeadGuarantor::STATUS_SUITABLE,
                ],
            ],
        ]));

        $this->assertSame(Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR, $lead->credit_type);
        $this->assertSame('ivan@example.com', $lead->email);
        $this->assertSame('Пловдив', $lead->city);
        $this->assertCount(1, $lead->guarantors);
        $this->assertDatabaseHas('lead_guarantors', [
            'lead_id' => $lead->id,
            'first_name' => 'Мария',
            'last_name' => 'Георгиева',
            'phone' => '0888111222',
            'status' => LeadGuarantor::STATUS_SUITABLE,
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

    public function test_create_lead_reuses_historical_assigned_user_for_normalized_phone_match(): void
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
            'phone' => '+359 888 123 456',
            'normalized_phone' => '0888123456',
            'email' => 'petar@example.com',
            'city' => 'София',
            'amount' => 12000,
            'status' => 'new',
            'assigned_user_id' => $historicalOperator->id,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'phone' => '0888 123 456',
        ]));

        $this->assertSame($historicalOperator->id, $lead->assigned_user_id);
        $this->assertSame('0888123456', $lead->phone);
        $this->assertSame('0888123456', $lead->normalized_phone);

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

    public function test_admin_can_return_attached_lead_to_primary_assignee(): void
    {
        $renata = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $anna->id,
            'additional_user_id' => $renata->id,
        ]));

        app(LeadService::class)->returnAttachedLeadToPrimary($lead, $renata);

        $lead->refresh();

        $this->assertSame($anna->id, $lead->assigned_user_id);
        $this->assertNull($lead->additional_user_id);
    }

    public function test_operator_can_return_attached_lead_to_primary_assignee_and_store_archive_state(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $anna->id,
            'additional_user_id' => $elena->id,
        ]));

        app(LeadService::class)->returnAttachedLeadToPrimary($lead, $elena);

        $lead->refresh();

        $this->assertSame($anna->id, $lead->assigned_user_id);
        $this->assertNull($lead->additional_user_id);
        $this->assertSame($elena->id, $lead->returned_additional_user_id);
        $this->assertNotNull($lead->returned_to_primary_at);
    }

    public function test_returning_attached_lead_stores_database_notification_for_primary_assignee(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $anna->id,
            'additional_user_id' => $elena->id,
        ]));

        app(LeadService::class)->returnAttachedLeadToPrimary($lead, $elena);

        $notification = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $anna->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($notification);
        $payload = json_decode((string) $notification->data, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Имате върната заявка към вас', $payload['title']);
        $this->assertStringContainsString($elena->name, $payload['body']);
    }

    public function test_returning_attached_lead_requires_current_additional_assignee(): void
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

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $anna->id,
            'additional_user_id' => $elena->id,
        ]));

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Заявката не е закачена към вас.');

        app(LeadService::class)->returnAttachedLeadToPrimary($lead, $renata);
    }

    public function test_returning_attached_lead_requires_primary_assignee(): void
    {
        $renata = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => null,
            'additional_user_id' => $renata->id,
        ]));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Изберете основен служител, преди да върнете заявката.');

        app(LeadService::class)->returnAttachedLeadToPrimary($lead, $renata);
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
            'privacy_consent' => false,
        ], $overrides);
    }
}
