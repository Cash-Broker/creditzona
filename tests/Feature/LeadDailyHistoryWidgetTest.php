<?php

namespace Tests\Feature;

use App\Filament\Widgets\LeadDailyHistoryWidget;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeadDailyHistoryWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_daily_history_widget_is_visible_only_to_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->actingAs($admin);
        $this->assertTrue(LeadDailyHistoryWidget::canView());

        $this->actingAs($operator);
        $this->assertFalse(LeadDailyHistoryWidget::canView());
    }

    public function test_daily_history_widget_groups_received_leads_by_sofia_day(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 10:00:00', 'UTC'));

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $firstTodayLead = Lead::query()->create($this->leadData([
            'phone' => '0888000001',
            'normalized_phone' => '0888000001',
            'email' => 'first@example.com',
        ]));
        $this->setLeadTimestamps($firstTodayLead, '2026-03-20 08:00:00');

        $secondTodayLead = Lead::query()->create($this->leadData([
            'phone' => '0888000002',
            'normalized_phone' => '0888000002',
            'email' => 'second@example.com',
        ]));
        $this->setLeadTimestamps($secondTodayLead, '2026-03-19 22:30:00');

        $yesterdayLead = Lead::query()->create($this->leadData([
            'phone' => '0888000003',
            'normalized_phone' => '0888000003',
            'email' => 'third@example.com',
        ]));
        $this->setLeadTimestamps($yesterdayLead, '2026-03-19 21:30:00');

        $this->actingAs($admin);

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $this->makeWidget()->exposedDailyHistoryRows();

        $this->assertCount(2, $rows);

        $this->assertSame('2026-03-20', $rows[0]['date_key']);
        $this->assertSame('20.03.2026', $rows[0]['date_label']);
        $this->assertSame(2, $rows[0]['total_leads']);
        $this->assertTrue($rows[0]['is_today']);

        $this->assertSame('2026-03-19', $rows[1]['date_key']);
        $this->assertSame('19.03.2026', $rows[1]['date_label']);
        $this->assertSame(1, $rows[1]['total_leads']);
        $this->assertFalse($rows[1]['is_today']);
    }

    private function makeWidget(): object
    {
        return new class extends LeadDailyHistoryWidget
        {
            /**
             * @return Collection<int, array<string, mixed>>
             */
            public function exposedDailyHistoryRows(): Collection
            {
                return $this->getDailyHistoryRows();
            }
        };
    }

    private function setLeadTimestamps(Lead $lead, string $createdAtUtc): void
    {
        $lead->forceFill([
            'created_at' => CarbonImmutable::parse($createdAtUtc, 'UTC'),
            'updated_at' => CarbonImmutable::parse($createdAtUtc, 'UTC'),
        ])->saveQuietly();
    }

    /**
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'middle_name' => null,
            'last_name' => 'Иванов',
            'egn' => null,
            'phone' => '0888000000',
            'normalized_phone' => '0888000000',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'workplace' => null,
            'job_title' => null,
            'salary' => null,
            'marital_status' => null,
            'children_under_18' => null,
            'salary_bank' => null,
            'credit_bank' => null,
            'documents' => null,
            'document_file_names' => null,
            'internal_notes' => null,
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'status' => 'new',
            'assigned_user_id' => null,
            'additional_user_id' => null,
            'returned_additional_user_id' => null,
            'returned_to_primary_at' => null,
            'source' => null,
            'utm_source' => null,
            'utm_campaign' => null,
            'utm_medium' => null,
            'gclid' => null,
            'privacy_consent_accepted' => false,
            'privacy_consent_accepted_at' => null,
            'privacy_consent_document_name' => null,
            'privacy_consent_document_path' => null,
        ], $overrides);
    }
}
