<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Lead;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadListPollingTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_list_page_polls_every_five_seconds(): void
    {
        $page = app(ListLeads::class);
        $table = LeadResource::table(Table::make($page));

        $this->assertSame('5s', $table->getPollingInterval());
    }

    public function test_leads_list_uses_checkbox_column_for_mark_for_later_and_enables_bulk_actions(): void
    {
        $table = LeadResource::table(Table::make(app(ListLeads::class)));

        $columnNames = collect($table->getColumns())
            ->map(fn ($column): string => $column->getName())
            ->values()
            ->all();

        $toolbarActionNames = collect($table->getToolbarActions())
            ->map(fn ($action): string => $action->getName())
            ->values()
            ->all();

        $this->assertSame(['status', 'marked_for_later'], array_slice($columnNames, 0, 2));
        $this->assertTrue($table->isSelectionEnabled());
        $this->assertContains('mark_selected_for_later', $toolbarActionNames);
        $this->assertContains('unmark_selected_for_later', $toolbarActionNames);
    }

    public function test_leads_list_rows_open_the_view_page_in_a_new_tab(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'egn' => '9001010001',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
        ]);

        $this->actingAs($admin);

        $table = LeadResource::table(Table::make(app(ListLeads::class)));

        $this->assertSame(
            LeadResource::getUrl('view', ['record' => $lead]),
            $table->getRecordUrl($lead),
        );
        $this->assertTrue($table->shouldOpenRecordUrlInNewTab($lead));
    }
}
