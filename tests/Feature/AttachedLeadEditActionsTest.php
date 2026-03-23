<?php

namespace Tests\Feature;

use App\Filament\Resources\AttachedLeads\Pages\EditAttachedLead;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttachedLeadEditActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_attached_lead_page_shows_save_cancel_and_return_actions(): void
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
            'credit_type' => 'consumer',
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'sms',
            'assigned_user_id' => $operator->id,
            'additional_user_id' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditAttachedLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->assertSee('Запази')
            ->assertSee('Отказ')
            ->assertSee('Върни');
    }

    public function test_edit_attached_lead_save_prunes_effectively_empty_guarantor_rows(): void
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
            'credit_type' => 'consumer',
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'sms',
            'assigned_user_id' => $operator->id,
            'additional_user_id' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditAttachedLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->fillForm([
                'guarantors' => [[
                    'status' => null,
                    'amount' => null,
                    'first_name' => null,
                    'middle_name' => null,
                    'last_name' => null,
                    'egn' => null,
                    'phone' => null,
                    'email' => null,
                    'city' => null,
                    'workplace' => null,
                    'job_title' => null,
                    'salary' => null,
                    'marital_status' => null,
                    'children_under_18' => null,
                    'salary_bank' => null,
                    'credit_bank' => null,
                    'documents' => [],
                    'document_file_names' => [],
                    'internal_notes' => '<p></p>',
                ]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('lead_guarantors', 0);
    }

    public function test_edit_attached_lead_save_allows_guarantor_without_status(): void
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
            'credit_type' => 'consumer',
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'sms',
            'assigned_user_id' => $operator->id,
            'additional_user_id' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditAttachedLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->fillForm([
                'guarantors' => [[
                    'status' => null,
                    'first_name' => 'Антон',
                    'last_name' => 'Колев',
                    'phone' => '0876997981',
                    'documents' => [],
                    'document_file_names' => [],
                    'internal_notes' => null,
                ]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('lead_guarantors', [
            'lead_id' => $lead->id,
            'first_name' => 'Антон',
            'last_name' => 'Колев',
            'phone' => '0876997981',
            'status' => null,
        ]);
    }
}
