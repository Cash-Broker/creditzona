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
            'first_name' => 'Ivan',
            'last_name' => 'Ivanov',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Plovdiv',
            'amount' => 10000,
            'status' => 'processed',
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
}
