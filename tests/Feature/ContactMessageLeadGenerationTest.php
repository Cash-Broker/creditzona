<?php

namespace Tests\Feature;

use App\Filament\Resources\AttachedContactMessageArchives\Pages\ViewAttachedContactMessageArchive;
use App\Filament\Resources\AttachedContactMessages\Pages\ViewAttachedContactMessage;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\ContactMessage;
use App\Models\Lead;
use App\Models\User;
use App\Services\ContactMessageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactMessageLeadGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_generate_lead_from_own_attached_contact_message(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'name' => 'Елена',
        ]);

        $message = ContactMessage::query()->create([
            'full_name' => 'Иван Петров Иванов',
            'phone' => '+359 888 123 456',
            'email' => 'ivan@example.com',
            'message' => 'Искам да ми се обадите за консултация.',
            'assigned_user_id' => $operator->id,
        ]);

        $lead = app(ContactMessageService::class)->createLeadFromMessage($message, $operator);

        $message->refresh();

        $this->assertSame(Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR, $lead->credit_type);
        $this->assertSame('Иван', $lead->first_name);
        $this->assertSame('Петров', $lead->middle_name);
        $this->assertSame('Иванов', $lead->last_name);
        $this->assertSame('0888123456', $lead->phone);
        $this->assertSame('0888123456', $lead->normalized_phone);
        $this->assertSame('ivan@example.com', $lead->email);
        $this->assertNull($lead->city);
        $this->assertSame(5000, $lead->amount);
        $this->assertSame('new', $lead->status);
        $this->assertSame('contact_message', $lead->source);
        $this->assertSame($operator->id, $lead->assigned_user_id);
        $this->assertStringContainsString('Контактна форма', (string) $lead->internal_notes);
        $this->assertStringContainsString('Искам да ми се обадите', (string) $lead->internal_notes);
        $this->assertSame($lead->id, $message->generated_lead_id);
        $this->assertNotNull($message->lead_generated_at);
    }

    public function test_generating_lead_from_same_contact_message_twice_reuses_existing_lead(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $message = ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение.',
            'assigned_user_id' => $operator->id,
        ]);

        $firstLead = app(ContactMessageService::class)->createLeadFromMessage($message, $operator);
        $secondLead = app(ContactMessageService::class)->createLeadFromMessage($message->fresh(), $operator);

        $this->assertTrue($firstLead->is($secondLead));
        $this->assertSame(1, Lead::query()->count());
    }

    public function test_operator_can_generate_lead_from_archived_attached_contact_message(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $message = ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово архивирано съобщение.',
            'assigned_user_id' => $operator->id,
            'archived_by_user_id' => $operator->id,
            'archived_at' => now(),
        ]);

        $lead = app(ContactMessageService::class)->createLeadFromMessage($message, $operator);

        $message->refresh();

        $this->assertSame($lead->id, $message->generated_lead_id);
        $this->assertNotNull($message->archived_at);
        $this->assertSame($operator->id, $lead->assigned_user_id);
    }

    public function test_lead_from_message_backfills_personal_data_from_previous_leads(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'middle_name' => 'Петров',
            'last_name' => 'Иванов',
            'egn' => '8501010000',
            'phone' => '0888123456',
            'email' => 'star@example.com',
            'city' => 'Пловдив',
            'workplace' => 'Строй ЕООД',
            'job_title' => 'Техник',
            'salary' => 1800,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 2,
            'salary_bank' => 'ОББ',
            'credit_bank' => 'ДСК',
            'movable_immovable_property' => 'Апартамент в Пловдив',
            'amount' => 6000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
        ])->guarantors()->create([
            'first_name' => 'Мария',
            'last_name' => 'Иванова',
            'phone' => '0888999999',
            'status' => 'suitable',
        ]);

        $message = ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '+359 888 123 456',
            'email' => 'ivan@example.com',
            'message' => 'Искам нова консултация.',
            'assigned_user_id' => $operator->id,
        ]);

        $lead = app(ContactMessageService::class)->createLeadFromMessage($message, $operator);

        $this->assertSame('8501010000', $lead->egn);
        $this->assertSame('Петров', $lead->middle_name);
        $this->assertSame('Пловдив', $lead->city);
        $this->assertSame('Строй ЕООД', $lead->workplace);
        $this->assertSame('Техник', $lead->job_title);
        $this->assertSame(1800, $lead->salary);
        $this->assertSame(Lead::MARITAL_STATUS_MARRIED, $lead->marital_status);
        $this->assertSame(2, $lead->children_under_18);
        $this->assertSame('ОББ', $lead->salary_bank);
        $this->assertSame('ДСК', $lead->credit_bank);
        $this->assertSame('Апартамент в Пловдив', $lead->movable_immovable_property);

        $this->assertSame('ivan@example.com', $lead->email);
        $this->assertSame(0, $lead->guarantors()->count());
        $this->assertStringContainsString('Искам нова консултация.', (string) $lead->internal_notes);
        $this->assertStringNotContainsString('Проверен клиент', (string) $lead->internal_notes);
    }

    public function test_operator_cannot_generate_lead_from_message_attached_to_another_operator(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $message = ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение.',
            'assigned_user_id' => $elena->id,
        ]);

        $this->expectException(AuthorizationException::class);

        app(ContactMessageService::class)->createLeadFromMessage($message, $anna);
    }

    public function test_attached_contact_message_view_action_redirects_to_generated_lead_edit_page(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $message = ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение.',
            'assigned_user_id' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ViewAttachedContactMessage::class, [
            'record' => (string) $message->getKey(),
        ])
            ->callAction('create_lead_from_contact_message')
            ->assertRedirect(LeadResource::getUrl('edit', [
                'record' => $message->fresh()->generated_lead_id,
            ]));
    }

    public function test_attached_contact_message_archive_view_action_redirects_to_generated_lead_edit_page(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $message = ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово архивирано съобщение.',
            'assigned_user_id' => $operator->id,
            'archived_by_user_id' => $operator->id,
            'archived_at' => now(),
        ]);

        $this->actingAs($operator);

        Livewire::test(ViewAttachedContactMessageArchive::class, [
            'record' => (string) $message->getKey(),
        ])
            ->callAction('create_lead_from_contact_message')
            ->assertRedirect(LeadResource::getUrl('edit', [
                'record' => $message->fresh()->generated_lead_id,
            ]));
    }
}
