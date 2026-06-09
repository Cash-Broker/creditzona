<?php

namespace Tests\Feature;

use App\Filament\Resources\ContractBatches\Pages\CreateContractBatch;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadMovableImmovablePropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_and_guarantor_models_persist_movable_immovable_property(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'egn' => '9001010000',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
            'movable_immovable_property' => 'Апартамент в гр. Пловдив; лек автомобил',
        ]);

        $guarantor = $lead->guarantors()->create([
            'first_name' => 'Мария',
            'last_name' => 'Иванова',
            'egn' => '9102020000',
            'phone' => '0888999999',
            'movable_immovable_property' => 'Къща в с. Марково',
        ]);

        $this->assertSame('Апартамент в гр. Пловдив; лек автомобил', $lead->fresh()->movable_immovable_property);
        $this->assertSame('Къща в с. Марково', $guarantor->fresh()->movable_immovable_property);
    }

    public function test_client_movable_immovable_property_persists_via_edit_form(): void
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
            'egn' => '9001010000',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditLead::class, ['record' => (string) $lead->getKey()])
            ->assertSee('Движимо/недвижимо имущество')
            ->set('data.movable_immovable_property', 'Апартамент в гр. Пловдив; лек автомобил')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'Апартамент в гр. Пловдив; лек автомобил',
            $lead->fresh()->movable_immovable_property,
        );
    }

    public function test_view_lead_shows_movable_immovable_property_for_client_and_guarantor(): void
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
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'egn' => '9001010000',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $operator->id,
            'movable_immovable_property' => 'Клиентско имущество: апартамент',
        ]);

        $lead->guarantors()->create([
            'first_name' => 'Мария',
            'last_name' => 'Иванова',
            'egn' => '9102020000',
            'phone' => '0888999999',
            'status' => LeadGuarantor::STATUS_SUITABLE,
            'movable_immovable_property' => 'Поръчителско имущество: къща',
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewLead::class, ['record' => (string) $lead->getKey()])
            ->assertSee('Движимо/недвижимо имущество')
            ->assertSee('Клиентско имущество: апартамент')
            ->assertSee('Поръчителско имущество: къща');
    }

    public function test_movable_immovable_property_is_not_present_in_contract_form(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateContractBatch::class)
            ->assertDontSee('Движимо/недвижимо имущество');
    }
}
