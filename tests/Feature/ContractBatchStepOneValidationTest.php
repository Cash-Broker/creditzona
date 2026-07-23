<?php

namespace Tests\Feature;

use App\Filament\Resources\ContractBatches\Pages\CreateContractBatch;
use App\Models\ContractBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractBatchStepOneValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_layout_requires_credit_data_fields_demanded_by_the_service(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateContractBatch::class)
            ->fillForm($this->stepOneData(ContractBatch::DOCUMENT_LAYOUT_FULL, [
                'financial' => [
                    'credit_count_in_institutions' => null,
                    'credit_count_in_banks' => null,
                    'total_loan_amount_eur' => null,
                    'commission_eur' => null,
                    'monthly_payments_eur' => null,
                    'net_income_eur' => null,
                    'post_service_credit_count' => null,
                    'post_service_monthly_repayment_burden_eur' => null,
                ],
            ]))
            ->call('create')
            ->assertHasFormErrors([
                'financial.credit_count_in_institutions' => 'required',
                'financial.credit_count_in_banks' => 'required',
                'financial.total_loan_amount_eur' => 'required',
                'financial.commission_eur' => 'required',
                'financial.monthly_payments_eur' => 'required',
                'financial.net_income_eur' => 'required',
                'financial.post_service_credit_count' => 'required',
                'financial.post_service_monthly_repayment_burden_eur' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('contract_batches', 0);
    }

    public function test_bridge_credit_layout_requires_credit_data_fields_demanded_by_the_service(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateContractBatch::class)
            ->fillForm($this->stepOneData(ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT, [
                'financial' => [
                    'monthly_payments_eur' => null,
                    'net_income_eur' => null,
                    'post_service_credit_count' => null,
                    'post_service_monthly_repayment_burden_eur' => null,
                ],
            ]))
            ->call('create')
            ->assertHasFormErrors([
                'financial.monthly_payments_eur' => 'required',
                'financial.net_income_eur' => 'required',
                'financial.post_service_credit_count' => 'required',
                'financial.post_service_monthly_repayment_burden_eur' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('contract_batches', 0);
    }

    public function test_single_credit_count_field_satisfies_the_active_credit_count_requirement(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateContractBatch::class)
            ->fillForm($this->stepOneData(ContractBatch::DOCUMENT_LAYOUT_FULL, [
                'financial' => [
                    'credit_count_in_institutions' => null,
                    'credit_count_in_banks' => 2,
                ],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('contract_batches', 1);
    }

    public function test_simplified_layout_does_not_require_post_service_fields(): void
    {
        $this->actingAsAdmin();

        // За опростените видове applyLayoutFieldMappings() слага defaults
        // за данните "след съдействие" и те не са задължителни в Стъпка 1.
        Livewire::test(CreateContractBatch::class)
            ->fillForm($this->stepOneData(ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED, [
                'financial' => [
                    'post_service_credit_count' => null,
                    'post_service_monthly_repayment_burden_eur' => null,
                ],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('contract_batches', 1);
    }

    public function test_simplified_no_guarantor_layout_does_not_require_net_income(): void
    {
        $this->actingAsAdmin();

        // Опростеният договор без поръчител няма заявка-искане, затова
        // нетният доход не е задължителен за него.
        Livewire::test(CreateContractBatch::class)
            ->fillForm($this->stepOneData(ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR, [
                'financial' => [
                    'net_income_eur' => null,
                    'post_service_credit_count' => null,
                    'post_service_monthly_repayment_burden_eur' => null,
                ],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('contract_batches', 1);
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Пълен и валиден набор данни за Стъпка 1, върху който тестовете
     * зануляват конкретни полета.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function stepOneData(string $layout, array $overrides = []): array
    {
        return array_replace_recursive([
            'document_layout' => $layout,
            'client' => [
                'city' => 'Пловдив',
                'full_name' => 'Иван Петров Иванов',
                'egn' => '8501010000',
                'permanent_address' => 'гр. Пловдив, ул. Тест 1',
                'id_card_number' => '111222333',
                'id_card_issued_at' => '2020-05-20',
                'id_card_issued_by' => 'МВР Пловдив',
            ],
            'co_applicant' => [
                'full_name' => 'Мария Петрова Иванова',
                'egn' => '8602020000',
                'permanent_address' => 'гр. София, ул. Тест 2',
                'id_card_number' => '999888777',
                'id_card_issued_at' => '2021-06-21',
                'id_card_issued_by' => 'МВР София',
            ],
            'financial' => [
                'credit_count_in_institutions' => 3,
                'credit_count_in_banks' => 2,
                'total_loan_amount_eur' => 30000,
                'commission_eur' => 2500,
                'monthly_payments_eur' => 1000,
                'net_income_eur' => 3000,
                'post_service_credit_count' => 1,
                'post_service_monthly_repayment_burden_eur' => 500,
            ],
        ], $overrides);
    }
}
