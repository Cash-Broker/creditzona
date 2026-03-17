<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\Widgets\LeadCommunicationWidget;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadCommunicationWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_sends_message_to_lead_chat(): void
    {
        $author = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $lead = Lead::query()->create($this->leadData());

        $this->actingAs($author);

        Livewire::test(LeadCommunicationWidget::class, [
            'record' => $lead,
        ])
            ->set('messageData.body', '  Ново вътрешно съобщение.  ')
            ->call('send')
            ->assertSee('Ново вътрешно съобщение.');

        $this->assertDatabaseHas('lead_messages', [
            'lead_id' => $lead->id,
            'user_id' => $author->id,
            'body' => 'Ново вътрешно съобщение.',
        ]);
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
