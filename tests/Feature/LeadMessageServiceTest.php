<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_message_stores_internal_chat_message_for_lead(): void
    {
        $author = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $lead = Lead::query()->create($this->leadData());

        $message = app(LeadMessageService::class)->createMessage($lead, $author, [
            'body' => 'Вътрешно съобщение по клиента.',
        ]);

        $this->assertDatabaseHas('lead_messages', [
            'id' => $message->id,
            'lead_id' => $lead->id,
            'user_id' => $author->id,
            'body' => 'Вътрешно съобщение по клиента.',
        ]);

        $this->assertTrue($message->lead->is($lead));
        $this->assertTrue($message->author->is($author));
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
            'internal_notes' => null,
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'status' => 'new',
            'assigned_user_id' => null,
            'source' => null,
            'utm_source' => null,
            'utm_campaign' => null,
            'utm_medium' => null,
            'gclid' => null,
        ], $overrides);
    }
}
