<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdminOverview;
use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_overview_widget_shows_global_stats_to_admin(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 10:00:00', 'UTC'));

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $todayLead = Lead::query()->create($this->leadData([
            'status' => 'new',
        ]));
        $this->setLeadTimestamps($todayLead, '2026-03-20 08:00:00');

        $todayLeadAroundMidnight = Lead::query()->create($this->leadData([
            'phone' => '0888000002',
            'normalized_phone' => '0888000002',
            'email' => 'second@example.com',
            'status' => 'new',
        ]));
        $this->setLeadTimestamps($todayLeadAroundMidnight, '2026-03-19 22:30:00');

        $yesterdayLead = Lead::query()->create($this->leadData([
            'phone' => '0888000003',
            'normalized_phone' => '0888000003',
            'email' => 'third@example.com',
            'status' => 'processed',
        ]));
        $this->setLeadTimestamps($yesterdayLead, '2026-03-19 21:30:00');

        ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение',
        ]);

        Blog::query()->create([
            'title' => 'Първа статия',
            'slug' => 'parva-statiya',
            'content' => 'Съдържание',
            'is_published' => true,
        ]);

        Faq::query()->create([
            'question' => 'Тестов въпрос',
            'answer' => 'Тестов отговор',
            'is_published' => true,
        ]);

        $this->actingAs($admin);

        $stats = $this->makeWidget()->exposedStats();

        $this->assertCount(6, $stats);
        $this->assertSame('Нови заявки', $stats[0]->getLabel());
        $this->assertSame(2, $stats[0]->getValue());
        $this->assertSame('Общо 3 заявки', $stats[0]->getDescription());
        $this->assertSame('Върнати към мен', $stats[1]->getLabel());
        $this->assertSame(0, $stats[1]->getValue());
        $this->assertSame('Получени заявки днес', $stats[2]->getLabel());
        $this->assertSame(2, $stats[2]->getValue());
        $this->assertSame('Занулява се всеки ден в 00:00 ч.', $stats[2]->getDescription());
        $this->assertSame('Контактни съобщения', $stats[3]->getLabel());
        $this->assertSame(1, $stats[3]->getValue());
        $this->assertSame('Публикувани статии', $stats[4]->getLabel());
        $this->assertSame(1, $stats[4]->getValue());
        $this->assertSame('Публикувани ЧЗВ', $stats[5]->getLabel());
        $this->assertSame(1, $stats[5]->getValue());
    }

    public function test_admin_overview_widget_shows_only_visible_leads_to_operator(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        Lead::query()->create($this->leadData([
            'assigned_user_id' => $operator->id,
            'status' => 'new',
        ]));
        Lead::query()->create($this->leadData([
            'phone' => '0888000002',
            'normalized_phone' => '0888000002',
            'email' => 'second@example.com',
            'additional_user_id' => $operator->id,
            'status' => 'processed',
        ]));
        Lead::query()->create($this->leadData([
            'phone' => '0888000003',
            'normalized_phone' => '0888000003',
            'email' => 'third@example.com',
            'assigned_user_id' => $otherOperator->id,
            'status' => 'new',
        ]));

        ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение',
        ]);

        Blog::query()->create([
            'title' => 'Първа статия',
            'slug' => 'parva-statiya',
            'content' => 'Съдържание',
            'is_published' => true,
        ]);

        Faq::query()->create([
            'question' => 'Тестов въпрос',
            'answer' => 'Тестов отговор',
            'is_published' => true,
        ]);

        $this->actingAs($operator);

        $stats = $this->makeWidget()->exposedStats();

        $this->assertCount(2, $stats);
        $this->assertSame('Моите заявки', $stats[0]->getLabel());
        $this->assertSame(1, $stats[0]->getValue());
        $this->assertSame('Общо 2 ваши заявки', $stats[0]->getDescription());
        $this->assertSame('Върнати към мен', $stats[1]->getLabel());
        $this->assertSame(0, $stats[1]->getValue());
    }

    private function makeWidget(): object
    {
        return new class extends AdminOverview
        {
            /**
             * @return array<Stat>
             */
            public function exposedStats(): array
            {
                return $this->getStats();
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
            'phone' => '0888000001',
            'normalized_phone' => '0888000001',
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
