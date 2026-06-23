<?php

namespace Tests\Feature;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use App\Filament\Resources\AttachedLeads\Pages\ListAttachedLeads;
use App\Models\Lead;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttachedLeadOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_attached_list_uses_attachment_date_as_default_sort(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $this->actingAs($operator);

        $table = AttachedLeadResource::table(Table::make(app(ListAttachedLeads::class)));
        $query = $table->getDefaultSort(Lead::query(), 'desc');

        $this->assertSame([
            ['column' => 'additional_assigned_at', 'direction' => 'desc'],
            ['column' => 'id', 'direction' => 'desc'],
        ], collect($query->getQuery()->orders)
            ->map(fn (array $order): array => [
                'column' => $order['column'] ?? null,
                'direction' => $order['direction'] ?? null,
            ])
            ->values()
            ->all());
    }

    public function test_attaching_a_lead_records_the_attachment_timestamp(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'egn' => '9001010000',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
        ]);

        $this->assertNull($lead->additional_assigned_at);

        $attachedAt = now()->subDays(2)->startOfSecond();

        $this->travelTo($attachedAt);
        $lead->update(['additional_user_id' => $operator->id]);
        $this->travelBack();

        $lead->refresh();
        $this->assertNotNull($lead->additional_assigned_at);
        $this->assertSame($attachedAt->timestamp, $lead->additional_assigned_at->timestamp);

        // Editing an unrelated field must not bump the attachment timestamp.
        $this->travelTo(now()->addDay());
        $lead->update(['first_name' => 'Петър']);
        $this->travelBack();

        $lead->refresh();
        $this->assertSame($attachedAt->timestamp, $lead->additional_assigned_at->timestamp);

        // Detaching clears the timestamp.
        $lead->update(['additional_user_id' => null]);
        $lead->refresh();
        $this->assertNull($lead->additional_assigned_at);
    }

    public function test_recently_attached_older_lead_sorts_above_a_newer_but_earlier_attached_lead(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        // Created long ago, but attached just now.
        $this->travelTo(now()->subDays(10));
        $oldLeadRecentlyAttached = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Стара',
            'last_name' => 'Заявка',
            'egn' => '9001010000',
            'phone' => '0888000001',
            'email' => 'stara@example.com',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
        ]);

        // Created more recently, but attached earlier.
        $this->travelTo(now()->subDays(5));
        $newLeadAttachedEarlier = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Нова',
            'last_name' => 'Заявка',
            'egn' => '9002020002',
            'phone' => '0888000002',
            'email' => 'nova@example.com',
            'amount' => 12000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
            'additional_user_id' => $operator->id,
        ]);

        $this->travelBack();
        $oldLeadRecentlyAttached->update(['additional_user_id' => $operator->id]);

        $this->actingAs($operator);

        $table = AttachedLeadResource::table(Table::make(app(ListAttachedLeads::class)));
        $sorted = $table->getDefaultSort(AttachedLeadResource::getEloquentQuery(), 'desc')->get();

        $this->assertSame(
            [$oldLeadRecentlyAttached->id, $newLeadAttachedEarlier->id],
            $sorted->pluck('id')->all(),
        );
    }

    public function test_attached_api_orders_recently_attached_lead_first(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $this->travelTo(now()->subDays(10));
        $oldLeadRecentlyAttached = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Стара',
            'last_name' => 'Заявка',
            'egn' => '9001010000',
            'phone' => '0888000001',
            'email' => 'stara@example.com',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
        ]);

        $this->travelTo(now()->subDays(5));
        $newLeadAttachedEarlier = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Нова',
            'last_name' => 'Заявка',
            'egn' => '9002020002',
            'phone' => '0888000002',
            'email' => 'nova@example.com',
            'amount' => 12000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
            'additional_user_id' => $operator->id,
        ]);

        $this->travelBack();
        $oldLeadRecentlyAttached->update(['additional_user_id' => $operator->id]);

        Sanctum::actingAs($operator);

        $response = $this->getJson('/api/leads/attached');

        $response->assertOk();

        $this->assertSame(
            [$oldLeadRecentlyAttached->id, $newLeadAttachedEarlier->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }
}
