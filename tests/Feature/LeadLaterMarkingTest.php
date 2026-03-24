<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadLaterMarkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_service_can_mark_and_unmark_lead_for_later(): void
    {
        $lead = Lead::query()->create($this->leadData());

        $this->assertFalse($lead->isMarkedForLater());

        $markedLead = app(LeadService::class)->setMarkedForLater($lead, true);

        $this->assertTrue($markedLead->isMarkedForLater());
        $this->assertNotNull($markedLead->marked_for_later_at);

        $unmarkedLead = app(LeadService::class)->setMarkedForLater($markedLead, false);

        $this->assertFalse($unmarkedLead->isMarkedForLater());
        $this->assertNull($unmarkedLead->marked_for_later_at);
    }

    public function test_leads_table_adds_record_class_for_leads_marked_for_later(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $this->actingAs($admin);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $operator->id,
            'marked_for_later_at' => now(),
        ]));

        $table = LeadResource::table(Table::make(app(ListLeads::class)));

        $this->assertContains('lead-record-later', $table->getRecordClasses($lead));
    }

    public function test_marked_for_later_leads_use_an_icon_indicator_without_recoloring_the_candidate_name(): void
    {
        $table = LeadResource::table(Table::make(app(ListLeads::class)));

        $lead = Lead::query()->create($this->leadData([
            'marked_for_later_at' => now(),
        ]));

        $column = $table->getColumn('full_name');

        $this->assertNotNull($column);

        $column->record($lead);
        $state = $column->getStateFromRecord();

        $this->assertNull($column->getColor($state));
        $this->assertSame('heroicon-m-clock', $column->getIcon($state));
        $this->assertSame('warning', $column->getIconColor($state));
        $this->assertSame(FontWeight::SemiBold, $column->getWeight($state));
    }

    /**
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'egn' => '9001010001',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => null,
            'additional_user_id' => null,
            'marked_for_later_at' => null,
        ], $overrides);
    }
}
