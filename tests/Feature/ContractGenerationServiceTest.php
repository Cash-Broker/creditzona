<?php

namespace Tests\Feature;

use App\Models\ContractBatch;
use App\Models\Lead;
use App\Models\User;
use App\Services\Contracts\ContractGenerationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ContractGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('legal');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-26 10:00:00', 'Europe/Sofia'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_generates_pdf_contract_batch_and_encrypts_input_payload(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'selected_document_types' => [
                ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ],
            'dates' => [
                'request_date' => '2026-03-20',
                'mediation_contract_date' => '2026-03-26',
                'company_promissory_note_due_date' => '2026-05-12',
            ],
        ]), $operator);

        $this->assertInstanceOf(ContractBatch::class, $batch);
        $this->assertSame('Иван Иванов', $batch->client_full_name);
        $this->assertSame('Мария Иванова', $batch->co_applicant_full_name);
        $this->assertCount(4, $batch->generated_documents ?? []);
        $this->assertSame('2028-03-26', data_get($batch->getDerivedInput(), 'dates.loan_due_date'));
        $this->assertSame('2026-03-24', data_get($batch->getDerivedInput(), 'dates.consultation_protocol_date'));
        $this->assertSame('2026-03-26', data_get($batch->getDerivedInput(), 'dates.company_promissory_note_issue_date'));

        $documentKeys = array_column($batch->generated_documents, 'document_key');

        $this->assertSame([
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT, 1),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT, 2),
        ], $documentKeys);

        foreach ($batch->generated_documents as $document) {
            $this->assertSame('application/pdf', $document['mime_type']);
            $this->assertStringEndsWith('.pdf', $document['download_name']);
            Storage::disk('legal')->assertExists($document['path']);
        }

        $storedPayload = DB::table('contract_batches')
            ->where('id', $batch->id)
            ->value('input_payload');

        $this->assertIsString($storedPayload);
        $this->assertStringNotContainsString('Иван Иванов', $storedPayload);
        $this->assertStringNotContainsString('8501010000', $storedPayload);

        if (class_exists(ZipArchive::class)) {
            $this->assertTrue($batch->archiveExists());
        }
    }

    public function test_it_allows_consultation_protocol_without_application_only_fields(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'financial' => array_merge($this->batchInput()['financial'], [
                'liabilities_total_eur' => null,
                'monthly_net_income_eur' => null,
            ]),
            'selected_document_types' => [
                ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            ],
        ]), $operator);

        $this->assertCount(1, $batch->generated_documents ?? []);
        $this->assertSame(
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            $batch->generated_documents[0]['document_type'],
        );
    }

    public function test_it_prefills_and_uses_source_lead_data_when_available(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

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
            'assigned_user_id' => $operator->id,
        ]);

        $lead->forceFill([
            'created_at' => CarbonImmutable::parse('2026-03-20 12:15:00', 'Europe/Sofia'),
        ])->saveQuietly();

        $guarantor = $lead->guarantors()->create([
            'first_name' => 'Maria',
            'middle_name' => 'Petrova',
            'last_name' => 'Ivanova',
            'egn' => '8602020000',
            'phone' => '0888999999',
            'email' => 'maria@example.com',
            'city' => 'Sofia',
        ]);

        $prefill = app(ContractGenerationService::class)->buildFormPrefillFromLead(
            $lead->fresh('guarantors'),
            $guarantor,
        );

        $this->assertSame($lead->id, $prefill['lead_id']);
        $this->assertSame($guarantor->id, $prefill['lead_guarantor_id']);
        $this->assertSame('Ivan Petrov Ivanov', data_get($prefill, 'client.full_name'));
        $this->assertSame('Maria Petrova Ivanova', data_get($prefill, 'co_applicant.full_name'));
        $this->assertSame('2026-03-20', data_get($prefill, 'dates.request_date'));
        $this->assertEquals(2600, data_get($prefill, 'financial.monthly_net_income_eur'));
        $this->assertEquals(12000, data_get($prefill, 'financial.loan_amount_eur'));
        $this->assertSame('Test Bank', data_get($prefill, 'loan.institution_name'));

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'lead_id' => $lead->id,
            'lead_guarantor_id' => $guarantor->id,
            'client' => [
                'full_name' => null,
                'egn' => null,
                'id_card_number' => '123456789',
                'id_card_issued_at' => '2020-05-20',
                'id_card_issued_by' => 'MVR Plovdiv',
                'permanent_address' => null,
                'email' => null,
            ],
            'co_applicant' => [
                'full_name' => null,
                'egn' => null,
                'id_card_number' => '987654321',
                'id_card_issued_at' => '2021-06-18',
                'id_card_issued_by' => 'MVR Sofia',
                'permanent_address' => null,
                'email' => null,
            ],
            'financial' => array_merge($this->batchInput()['financial'], [
                'monthly_net_income_eur' => null,
                'loan_amount_eur' => null,
            ]),
            'loan' => array_merge($this->batchInput()['loan'], [
                'institution_name' => null,
                'creditor_name' => null,
            ]),
            'dates' => [
                'request_date' => null,
            ],
            'selected_document_types' => [
                ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
                ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_DECLARATION,
            ],
        ]), $operator);

        $this->assertSame($lead->id, $batch->lead_id);
        $this->assertSame('2026-03-20', data_get($batch->getSubmittedInput(), 'dates.request_date'));
        $this->assertSame('Ivan Petrov Ivanov', data_get($batch->getSubmittedInput(), 'client.full_name'));
        $this->assertSame('Maria Petrova Ivanova', data_get($batch->getSubmittedInput(), 'co_applicant.full_name'));
        $this->assertEquals(2600, data_get($batch->getSubmittedInput(), 'financial.monthly_net_income_eur'));
        $this->assertEquals(12000, data_get($batch->getSubmittedInput(), 'financial.loan_amount_eur'));
        $this->assertSame('Test Bank', data_get($batch->getSubmittedInput(), 'loan.institution_name'));
        $this->assertSame('Test Bank', data_get($batch->getSubmittedInput(), 'loan.creditor_name'));
    }

    public function test_it_applies_editable_date_defaults_and_manual_overrides(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'selected_document_types' => [
                ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
                ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
                ContractBatch::DOCUMENT_TYPE_DECLARATION,
            ],
            'dates' => [
                'request_date' => '2026-03-20',
                'mediation_contract_date' => '2026-03-18',
                'mediation_protocol_date' => '2026-03-28',
                'company_promissory_note_due_date' => '2026-05-12',
                'loan_agreement_date' => '2026-04-01',
                'co_applicant_promissory_note_issue_date' => '2026-03-29',
                'co_applicant_promissory_note_due_date' => '2026-05-13',
                'declaration_date' => '2026-03-30',
            ],
        ]), $operator);

        $this->assertSame('2026-03-28', data_get($batch->getDerivedInput(), 'dates.mediation_protocol_date'));
        $this->assertSame('2026-03-26', data_get($batch->getDerivedInput(), 'dates.company_promissory_note_issue_date'));
        $this->assertSame('2026-05-12', data_get($batch->getDerivedInput(), 'dates.company_promissory_note_due_date'));
        $this->assertSame('2026-04-01', data_get($batch->getDerivedInput(), 'dates.loan_agreement_date'));
        $this->assertSame('2028-04-01', data_get($batch->getDerivedInput(), 'dates.loan_due_date'));
        $this->assertSame('2026-03-29', data_get($batch->getDerivedInput(), 'dates.co_applicant_promissory_note_issue_date'));
        $this->assertSame('2026-05-13', data_get($batch->getDerivedInput(), 'dates.co_applicant_promissory_note_due_date'));
        $this->assertSame('2026-03-30', data_get($batch->getDerivedInput(), 'dates.declaration_date'));
        $this->assertCount(6, $batch->generated_documents ?? []);
    }

    public function test_it_requires_co_applicant_for_declaration(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Попълнете трите имена за съкредитоискателя.');

        app(ContractGenerationService::class)->createBatch($this->batchInput([
            'co_applicant' => [
                'full_name' => null,
                'egn' => null,
                'id_card_number' => null,
                'id_card_issued_at' => null,
                'id_card_issued_by' => null,
                'permanent_address' => null,
                'email' => null,
            ],
            'selected_document_types' => [
                ContractBatch::DOCUMENT_TYPE_DECLARATION,
            ],
        ]), $operator);
    }

    /**
     * @return array<string, mixed>
     */
    private function batchInput(array $overrides = []): array
    {
        $input = array_replace_recursive([
            'company_key' => ContractBatch::COMPANY_REKREDO_KONSULT_DPK,
            'client' => [
                'full_name' => 'Иван Иванов',
                'egn' => '8501010000',
                'id_card_number' => '123456789',
                'id_card_issued_at' => '2020-05-20',
                'id_card_issued_by' => 'МВР Пловдив',
                'permanent_address' => 'гр. Пловдив, ул. Тест 1',
                'email' => 'ivan@example.com',
            ],
            'co_applicant' => [
                'full_name' => 'Мария Иванова',
                'egn' => '8602020000',
                'id_card_number' => '987654321',
                'id_card_issued_at' => '2021-06-18',
                'id_card_issued_by' => 'МВР Пловдив',
                'permanent_address' => 'гр. Пловдив, ул. Тест 2',
                'email' => 'maria@example.com',
            ],
            'financial' => [
                'active_credit_count' => 3,
                'liabilities_total_eur' => 10000,
                'monthly_repayment_burden_eur' => 450,
                'monthly_net_income_eur' => 2200,
                'post_service_credit_count' => 1,
                'post_service_monthly_repayment_burden_eur' => 180,
                'fee_eur' => 250,
                'co_applicant_promissory_note_amount_eur' => 9800,
                'loan_amount_eur' => 8000,
                'loan_return_amount_eur' => 9800,
                'loan_installment_eur' => 410,
            ],
            'loan' => [
                'institution_name' => 'ИПОТЕХ СОФКОМ АД',
                'credit_agreement_number' => 'ПК-2026-001',
                'creditor_name' => 'ИПОТЕХ СОФКОМ АД',
            ],
            'dates' => [
                'request_date' => '2026-03-20',
                'mediation_contract_date' => '2026-03-26',
                'company_promissory_note_due_date' => '2026-05-12',
                'co_applicant_promissory_note_due_date' => '2026-05-13',
            ],
            'selected_document_types' => [
                ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
                ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            ],
        ], $overrides);

        if (array_key_exists('selected_document_types', $overrides)) {
            $input['selected_document_types'] = $overrides['selected_document_types'];
        }

        return $input;
    }
}
