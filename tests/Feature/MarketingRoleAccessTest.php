<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Widgets\AdminOverview;
use App\Filament\Widgets\LeadDailyHistoryWidget;
use App\Filament\Widgets\MarketingLeadsByCreditTypeChartWidget;
use App\Filament\Widgets\MarketingLeadsByDayChartWidget;
use App\Filament\Widgets\MarketingLeadsBySourceChartWidget;
use App\Filament\Widgets\MarketingOverviewWidget;
use App\Models\Lead;
use App\Models\User;
use App\Policies\AdminDocumentPolicy;
use App\Policies\BlogPolicy;
use App\Policies\CalendarEventPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\ContractBatchPolicy;
use App\Policies\FaqPolicy;
use App\Policies\LeadPolicy;
use Carbon\CarbonImmutable;
use Filament\Panel;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_marketing_role_helpers_are_mutually_exclusive(): void
    {
        $marketing = User::factory()->make(['role' => User::ROLE_MARKETING]);
        $admin = User::factory()->make(['role' => User::ROLE_ADMIN]);
        $operator = User::factory()->make(['role' => User::ROLE_OPERATOR]);

        $this->assertTrue($marketing->isMarketing());
        $this->assertFalse($marketing->isAdmin());
        $this->assertFalse($marketing->isOperator());

        $this->assertFalse($admin->isMarketing());
        $this->assertFalse($operator->isMarketing());
    }

    public function test_marketing_user_can_access_admin_panel(): void
    {
        $marketing = User::factory()->make(['role' => User::ROLE_MARKETING]);
        $nonStaff = User::factory()->make(['role' => 'customer']);
        $adminPanel = (new Panel)->id('admin');

        $this->assertTrue($marketing->canAccessPanel($adminPanel));
        $this->assertFalse($nonStaff->canAccessPanel($adminPanel));
    }

    public function test_all_resource_policies_deny_viewing_for_marketing_role(): void
    {
        $marketing = User::factory()->make(['role' => User::ROLE_MARKETING]);

        $this->assertFalse((new BlogPolicy)->viewAny($marketing));
        $this->assertFalse((new FaqPolicy)->viewAny($marketing));
        $this->assertFalse((new LeadPolicy)->viewAny($marketing));
        $this->assertFalse((new ContactMessagePolicy)->viewAny($marketing));
        $this->assertFalse((new CalendarEventPolicy)->viewAny($marketing));
        $this->assertFalse((new AdminDocumentPolicy)->viewAny($marketing));
        $this->assertFalse((new ContractBatchPolicy)->viewAny($marketing));
    }

    public function test_marketing_overview_widget_is_visible_only_to_marketing_role(): void
    {
        $marketing = User::factory()->create(['role' => User::ROLE_MARKETING]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($marketing);
        $this->assertTrue(MarketingOverviewWidget::canView());

        $this->actingAs($admin);
        $this->assertFalse(MarketingOverviewWidget::canView());

        $this->actingAs($operator);
        $this->assertFalse(MarketingOverviewWidget::canView());
    }

    public function test_admin_widgets_are_hidden_from_marketing_role(): void
    {
        $marketing = User::factory()->create(['role' => User::ROLE_MARKETING]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($marketing);
        $this->assertFalse(AdminOverview::canView());
        $this->assertFalse(LeadDailyHistoryWidget::canView());

        $this->actingAs($admin);
        $this->assertTrue(AdminOverview::canView());
        $this->assertTrue(LeadDailyHistoryWidget::canView());
    }

    public function test_marketing_overview_widget_aggregates_lead_counts_correctly(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-21 23:59:00', 'Europe/Sofia'));

        $this->createLeadAt('2026-05-21 09:00:00');
        $this->createLeadAt('2026-05-21 22:30:00', '0888000002', 'two@example.com');
        $this->createLeadAt('2026-05-19 12:00:00', '0888000003', 'three@example.com');
        $this->createLeadAt('2026-05-05 09:00:00', '0888000004', 'four@example.com');
        $this->createLeadAt('2026-04-15 09:00:00', '0888000005', 'five@example.com');

        $marketing = User::factory()->create(['role' => User::ROLE_MARKETING]);
        $this->actingAs($marketing);

        $stats = $this->makeMarketingWidget()->exposedStats();

        $this->assertCount(4, $stats);
        $this->assertSame('Запитвания днес', $stats[0]->getLabel());
        $this->assertSame(2, $stats[0]->getValue());
        $this->assertSame('Запитвания тази седмица', $stats[1]->getLabel());
        $this->assertSame(3, $stats[1]->getValue());
        $this->assertSame('Запитвания този месец', $stats[2]->getLabel());
        $this->assertSame(4, $stats[2]->getValue());
        $this->assertSame('Всички запитвания', $stats[3]->getLabel());
        $this->assertSame(5, $stats[3]->getValue());
    }

    public function test_marketing_overview_widget_renders_trend_description_with_percent_change(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-21 10:00:00', 'Europe/Sofia'));

        $this->createLeadAt('2026-05-21 08:00:00');
        $this->createLeadAt('2026-05-21 09:00:00', '0888000002', 'two@example.com');
        $this->createLeadAt('2026-05-21 09:30:00', '0888000003', 'three@example.com');

        $this->createLeadAt('2026-05-20 09:00:00', '0888000010', 'y1@example.com');

        $marketing = User::factory()->create(['role' => User::ROLE_MARKETING]);
        $this->actingAs($marketing);

        $stats = $this->makeMarketingWidget()->exposedStats();

        $this->assertSame('+200% спрямо вчера', $stats[0]->getDescription());
    }

    public function test_chart_widgets_are_visible_only_to_marketing_role(): void
    {
        $marketing = User::factory()->create(['role' => User::ROLE_MARKETING]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($marketing);
        $this->assertTrue(MarketingLeadsByDayChartWidget::canView());
        $this->assertTrue(MarketingLeadsByCreditTypeChartWidget::canView());
        $this->assertTrue(MarketingLeadsBySourceChartWidget::canView());

        $this->actingAs($admin);
        $this->assertFalse(MarketingLeadsByDayChartWidget::canView());
        $this->assertFalse(MarketingLeadsByCreditTypeChartWidget::canView());
        $this->assertFalse(MarketingLeadsBySourceChartWidget::canView());
    }

    public function test_leads_by_day_chart_buckets_counts_by_sofia_date(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-21 23:00:00', 'Europe/Sofia'));

        $this->createLeadAt('2026-05-21 08:00:00');
        $this->createLeadAt('2026-05-21 14:00:00', '0888000002', 'two@example.com');
        $this->createLeadAt('2026-05-20 10:00:00', '0888000003', 'three@example.com');
        $this->createLeadAt('2026-04-15 09:00:00', '0888000004', 'four@example.com');

        $widget = new class extends MarketingLeadsByDayChartWidget
        {
            /**
             * @return array<string, mixed>
             */
            public function exposedData(): array
            {
                return $this->getData();
            }
        };

        $widget->filters = [
            'start_date' => '2026-05-19',
            'end_date' => '2026-05-21',
        ];

        $data = $widget->exposedData();

        $this->assertSame(['19.05', '20.05', '21.05'], $data['labels']);
        $this->assertSame([0, 1, 2], $data['datasets'][0]['data']);
    }

    public function test_leads_by_credit_type_chart_groups_by_type(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-21 12:00:00', 'Europe/Sofia'));

        $this->createLeadAt('2026-05-20 10:00:00', '0888100001', 'c1@example.com', Lead::CREDIT_TYPE_CONSUMER);
        $this->createLeadAt('2026-05-20 11:00:00', '0888100002', 'c2@example.com', Lead::CREDIT_TYPE_CONSUMER);
        $this->createLeadAt('2026-05-20 12:00:00', '0888100003', 'm1@example.com', Lead::CREDIT_TYPE_MORTGAGE);
        $this->createLeadAt('2026-05-20 13:00:00', '0888100004', 'g1@example.com', Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR);

        $widget = new class extends MarketingLeadsByCreditTypeChartWidget
        {
            /**
             * @return array<string, mixed>
             */
            public function exposedData(): array
            {
                return $this->getData();
            }
        };

        $widget->filters = [
            'start_date' => '2026-05-19',
            'end_date' => '2026-05-21',
        ];

        $data = $widget->exposedData();

        $this->assertSame(['Потребителски', 'Ипотечен', 'С поръчител'], $data['labels']);
        $this->assertSame([2, 1, 1], $data['datasets'][0]['data']);
    }

    public function test_leads_by_source_chart_returns_top_sources_with_fallback_for_missing(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-21 12:00:00', 'Europe/Sofia'));

        $this->createLeadAt('2026-05-20 09:00:00', '0888200001', 's1@example.com', utmSource: 'google');
        $this->createLeadAt('2026-05-20 10:00:00', '0888200002', 's2@example.com', utmSource: 'google');
        $this->createLeadAt('2026-05-20 11:00:00', '0888200003', 's3@example.com', utmSource: 'facebook');
        $this->createLeadAt('2026-05-20 12:00:00', '0888200004', 's4@example.com', utmSource: null);

        $widget = new class extends MarketingLeadsBySourceChartWidget
        {
            /**
             * @return array<string, mixed>
             */
            public function exposedData(): array
            {
                return $this->getData();
            }
        };

        $widget->filters = [
            'start_date' => '2026-05-19',
            'end_date' => '2026-05-21',
        ];

        $data = $widget->exposedData();

        $this->assertSame(['google', 'facebook', 'Директни / органични'], $data['labels']);
        $this->assertSame([2, 1, 1], $data['datasets'][0]['data']);
    }

    public function test_marketing_user_is_redirected_from_resource_urls_to_dashboard(): void
    {
        $marketing = User::factory()->create(['role' => User::ROLE_MARKETING]);

        $this->actingAs($marketing)
            ->get(LeadResource::getUrl('index'))
            ->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_marketing_user_can_view_dashboard(): void
    {
        $marketing = User::factory()->create(['role' => User::ROLE_MARKETING]);

        $this->actingAs($marketing)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertOk();
    }

    public function test_admin_user_is_not_affected_by_marketing_redirect_middleware(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $this->actingAs($admin)
            ->get(LeadResource::getUrl('index'))
            ->assertOk();
    }

    private function makeMarketingWidget(): object
    {
        return new class extends MarketingOverviewWidget
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

    private function createLeadAt(
        string $createdAtSofia,
        string $phone = '0888000001',
        string $email = 'one@example.com',
        string $creditType = Lead::CREDIT_TYPE_CONSUMER,
        ?string $utmSource = null,
    ): Lead {
        $lead = Lead::query()->create([
            'credit_type' => $creditType,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => $phone,
            'normalized_phone' => $phone,
            'email' => $email,
            'city' => 'София',
            'amount' => 10000,
            'status' => 'new',
            'utm_source' => $utmSource,
        ]);

        $createdAt = CarbonImmutable::parse($createdAtSofia, 'Europe/Sofia')->utc();

        $lead->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $lead;
    }
}
