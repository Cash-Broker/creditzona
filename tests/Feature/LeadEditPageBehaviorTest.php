<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class LeadEditPageBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_lead_page_redirects_to_listing_after_save_without_communication_widget(): void
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

        Livewire::test(EditLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->assertDontSee('Комуникация')
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(LeadResource::getUrl('index'));
    }

    public function test_view_lead_page_does_not_show_communication_widget(): void
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

        Livewire::test(ViewLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->assertDontSee('Комуникация');
    }

    public function test_edit_lead_page_appends_new_client_note_to_history(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
            'name' => 'Рената',
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
            'internal_notes' => '[23.03.2026 10:00] Анна: Първа бележка',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->fillForm([
                'lead_existing_internal_notes' => '[23.03.2026 10:00] Анна: Първа бележка',
                'lead_new_internal_note' => 'Втора бележка',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $lead->refresh();

        $this->assertStringContainsString('Първа бележка', (string) $lead->internal_notes);
        $this->assertStringContainsString('Рената: Втора бележка', (string) $lead->internal_notes);
    }

    public function test_edit_lead_page_notifies_new_additional_assignee_after_save(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $primaryOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $additionalOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
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
            'assigned_user_id' => $primaryOperator->id,
            'additional_user_id' => null,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->fillForm([
                'additional_user_id' => $additionalOperator->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $notification = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $additionalOperator->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($notification);

        $payload = json_decode((string) $notification->data, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Имате нова заявка към вас', $payload['title']);
        $this->assertStringContainsString($admin->name, $payload['body']);
    }

    public function test_edit_lead_page_allows_status_change_without_egn_for_sms(): void
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
            'egn' => null,
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->fillForm([
                'status' => 'sms',
                'egn' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('sms', $lead->fresh()->status);
    }
}
