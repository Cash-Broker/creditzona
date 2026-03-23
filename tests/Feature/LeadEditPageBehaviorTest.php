<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadEditPageBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_lead_page_shows_communication_and_redirects_to_listing_after_save(): void
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
            ->assertSee('Комуникация')
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
}
