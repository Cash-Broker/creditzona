<?php

namespace Tests\Feature;

use App\Filament\Resources\ContractBatches\Pages\CreateContractBatch;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractBatchLeadPrefillTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_contract_batch_page_prefills_data_from_source_lead(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = $this->createLeadForContracts($operator);
        $guarantor = $lead->guarantors()->firstOrFail();

        $this->actingAs($operator);

        Livewire::withQueryParams([
            'lead_id' => $lead->id,
        ])->test(CreateContractBatch::class)
            ->assertSet('data.lead_id', $lead->id)
            ->assertSet('data.lead_guarantor_id', $guarantor->id)
            ->assertSet('data.client.full_name', 'Ivan Petrov Ivanov')
            ->assertSet('data.client.egn', '8501010000')
            ->assertSet('data.co_applicant.full_name', 'Maria Petrova Ivanova')
            ->assertSet('data.dates.request_date', fn (mixed $state): bool => is_string($state) && str_starts_with($state, '2026-03-20'))
            ->assertSet('data.financial.monthly_net_income_eur', 2600)
            ->assertSet('data.financial.loan_amount_eur', 12000)
            ->assertSet('data.loan.institution_name', 'Test Bank');
    }

    public function test_create_contract_batch_page_does_not_prefill_inaccessible_lead(): void
    {
        $assignedOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $lead = $this->createLeadForContracts($assignedOperator);

        $this->actingAs($otherOperator);

        Livewire::withQueryParams([
            'lead_id' => $lead->id,
        ])->test(CreateContractBatch::class)
            ->assertSet('data.lead_id', null)
            ->assertSet('data.client.full_name', null)
            ->assertSet('data.dates.request_date', null);
    }

    public function test_lead_pages_show_generate_contracts_action(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = $this->createLeadForContracts($operator);

        $this->actingAs($admin);

        Livewire::test(ViewLead::class, [
            'record' => (string) $lead->getKey(),
        ])->assertSee('Генерирай договори');

        Livewire::test(EditLead::class, [
            'record' => (string) $lead->getKey(),
        ])->assertSee('Генерирай договори');
    }

    private function createLeadForContracts(User $assignedUser): Lead
    {
        $lead = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Ivan',
            'middle_name' => 'Petrov',
            'last_name' => 'Ivanov',
            'egn' => '8501010000',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Plovdiv',
            'salary' => 2600,
            'credit_bank' => 'Test Bank',
            'amount' => 12000,
            'status' => 'new',
            'assigned_user_id' => $assignedUser->id,
        ]);

        $lead->forceFill([
            'created_at' => CarbonImmutable::parse('2026-03-20 12:15:00', 'Europe/Sofia'),
        ])->saveQuietly();

        $lead->guarantors()->create([
            'first_name' => 'Maria',
            'middle_name' => 'Petrova',
            'last_name' => 'Ivanova',
            'egn' => '8602020000',
            'phone' => '0888999999',
            'email' => 'maria@example.com',
            'city' => 'Sofia',
        ]);

        return $lead->fresh('guarantors');
    }
}
