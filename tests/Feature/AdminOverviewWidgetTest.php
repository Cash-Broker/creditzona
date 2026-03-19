<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdminOverview;
use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_overview_widget_shows_global_stats_to_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Lead::query()->create($this->leadData([
            'status' => 'new',
        ]));
        Lead::query()->create($this->leadData([
            'phone' => '0888000002',
            'normalized_phone' => '0888000002',
            'email' => 'second@example.com',
            'status' => 'new',
        ]));
        Lead::query()->create($this->leadData([
            'phone' => '0888000003',
            'normalized_phone' => '0888000003',
            'email' => 'third@example.com',
            'status' => 'processed',
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

        $this->actingAs($admin);

        $stats = $this->makeWidget()->exposedStats();

        $this->assertCount(4, $stats);
        $this->assertSame('Нови заявки', $stats[0]->getLabel());
        $this->assertSame(2, $stats[0]->getValue());
        $this->assertSame('Общо 3 заявки', $stats[0]->getDescription());
        $this->assertSame('Контактни съобщения', $stats[1]->getLabel());
        $this->assertSame(1, $stats[1]->getValue());
        $this->assertSame('Публикувани статии', $stats[2]->getLabel());
        $this->assertSame(1, $stats[2]->getValue());
        $this->assertSame('Публикувани ЧЗВ', $stats[3]->getLabel());
        $this->assertSame(1, $stats[3]->getValue());
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

        $this->assertCount(1, $stats);
        $this->assertSame('Моите заявки', $stats[0]->getLabel());
        $this->assertSame(1, $stats[0]->getValue());
        $this->assertSame('Общо 2 ваши заявки', $stats[0]->getDescription());
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
