<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\ReturnedLeadArchives\Pages\ListReturnedLeadArchives;
use App\Filament\Resources\ReturnedLeadArchives\ReturnedLeadArchiveResource;
use App\Filament\Resources\ReturnedToMeLeads\Pages\ListReturnedToMeLeads;
use App\Filament\Resources\ReturnedToMeLeads\ReturnedToMeLeadResource;
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

    public function test_returned_to_me_list_uses_latest_return_date_as_default_sort(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $this->actingAs($operator);

        $table = ReturnedToMeLeadResource::table(Table::make(app(ListReturnedToMeLeads::class)));
        $query = $table->getDefaultSort(Lead::query(), 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
        $this->assertSame([
            ['column' => 'returned_to_primary_at', 'direction' => 'desc'],
            ['column' => 'id', 'direction' => 'desc'],
        ], collect($query->getQuery()->orders)
            ->map(fn (array $order): array => [
                'column' => $order['column'] ?? null,
                'direction' => $order['direction'] ?? null,
            ])
            ->values()
            ->all());
    }

    public function test_returned_archive_list_uses_latest_return_date_as_default_sort(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $this->actingAs($admin);

        $table = ReturnedLeadArchiveResource::table(Table::make(app(ListReturnedLeadArchives::class)));
        $query = $table->getDefaultSort(Lead::query(), 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
        $this->assertSame([
            ['column' => 'returned_to_primary_at', 'direction' => 'desc'],
            ['column' => 'id', 'direction' => 'desc'],
        ], collect($query->getQuery()->orders)
            ->map(fn (array $order): array => [
                'column' => $order['column'] ?? null,
                'direction' => $order['direction'] ?? null,
            ])
            ->values()
            ->all());
    }
}
