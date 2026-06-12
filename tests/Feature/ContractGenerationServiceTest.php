<?php

namespace Tests\Feature;

use App\Models\ContractBatch;
use App\Models\Lead;
use App\Models\LeadGuarantor;
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

    public function test_it_generates_pdf_and_docx_contract_batch_and_encrypts_input_payload(): void
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
            $this->assertArrayHasKey('variants', $document);
            $this->assertArrayHasKey(ContractBatch::DOCUMENT_VARIANT_DOCX, $document['variants']);

            $docxVariant = $document['variants'][ContractBatch::DOCUMENT_VARIANT_DOCX];

            $this->assertSame(
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                $docxVariant['mime_type'],
            );
            $this->assertStringEndsWith('.docx', $docxVariant['download_name']);
            Storage::disk('legal')->assertExists($docxVariant['path']);
        }

        $this->assertTrue($batch->combinedPdfExists());
        Storage::disk('legal')->assertExists($batch->combined_pdf_path);
        $this->assertStringEndsWith('.pdf', $batch->combined_pdf_file_name);

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
        $this->assertArrayNotHasKey('city', $prefill['client']);
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

    public function test_loan_only_layout_generates_loan_agreement_and_promissory_note(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_LOAN_ONLY,
            'client' => [
                'city' => 'Пловдив',
            ],
            'selected_document_types' => [],
        ]), $operator);

        $this->assertSame(ContractBatch::DOCUMENT_LAYOUT_LOAN_ONLY, $batch->document_layout);
        $this->assertSame('Пловдив', $batch->client_city);
        $this->assertSame([
            ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
        ], $batch->selected_document_types);
        $this->assertCount(3, $batch->generated_documents ?? []);
    }

    public function test_simplified_layout_generates_application_protocol_loan_and_promissory_note(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
            'client' => [
                'city' => 'Пловдив',
            ],
            'financial' => array_merge($this->batchInput()['financial'], [
                'credit_count_in_institutions' => 2,
                'institution_count' => 2,
                'credit_count_in_banks' => 1,
                'bank_count' => 1,
                'total_loan_amount_eur' => 12000,
                'commission_eur' => 600,
                'monthly_payments_eur' => 410,
                'private_loans_eur' => 0,
                'net_income_eur' => 2200,
                'court_required_eur' => 0,
            ]),
            'selected_document_types' => [],
        ]), $operator);

        $this->assertSame(ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED, $batch->document_layout);
        $this->assertSame([
            ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ContractBatch::DOCUMENT_TYPE_DECLARATION,
        ], $batch->selected_document_types);
        $this->assertSame(2, data_get($batch->getSubmittedInput(), 'financial.institution_count'));
        $this->assertEquals(12000, data_get($batch->getSubmittedInput(), 'financial.total_loan_amount_eur'));
    }

    public function test_simplified_no_guarantor_layout_generates_three_documents_without_co_applicant(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR,
            'client' => [
                'city' => 'Пловдив',
            ],
            'co_applicant' => [
                'full_name' => null,
                'egn' => null,
                'id_card_number' => null,
                'id_card_issued_at' => null,
                'id_card_issued_by' => null,
                'permanent_address' => null,
                'email' => null,
            ],
            'financial' => array_merge($this->batchInput()['financial'], [
                'credit_count_in_institutions' => 1,
                'institution_count' => 1,
                'credit_count_in_banks' => 1,
                'bank_count' => 1,
                'total_loan_amount_eur' => 8000,
                'commission_eur' => 400,
                'monthly_payments_eur' => 300,
                'net_income_eur' => 1800,
            ]),
            'dates' => [
                'request_date' => '2026-03-20',
                'consultation_contract_date' => '2026-03-20',
                'company_promissory_note_due_date' => '2026-05-12',
            ],
            'selected_document_types' => [],
        ]), $operator);

        $this->assertSame(ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR, $batch->document_layout);
        $this->assertNull($batch->co_applicant_full_name);
        $this->assertNull(data_get($batch->getSubmittedInput(), 'co_applicant.full_name'));
        $this->assertSame([
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
        ], $batch->selected_document_types);

        $documentKeys = array_column($batch->generated_documents ?? [], 'document_key');

        $this->assertSame([
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE),
        ], $documentKeys);

        $this->assertCount(3, $batch->generated_documents ?? []);
        $this->assertTrue($batch->combinedPdfExists());
    }

    public function test_full_layout_generates_all_nine_document_types(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_FULL,
            'client' => [
                'city' => 'Пловдив',
            ],
            'dates' => [
                'request_date' => '2026-03-20',
                'mediation_contract_date' => '2026-03-26',
                'consultation_contract_date' => '2026-03-15',
                'company_promissory_note_due_date' => '2026-05-12',
                'co_applicant_promissory_note_due_date' => '2026-05-13',
            ],
            'selected_document_types' => [],
        ]), $operator);

        $this->assertSame(ContractBatch::DOCUMENT_LAYOUT_FULL, $batch->document_layout);
        $this->assertSame(
            ContractBatch::getDocumentTypesForLayout(ContractBatch::DOCUMENT_LAYOUT_FULL),
            $batch->selected_document_types,
        );
        $this->assertSame('2026-03-15', data_get($batch->getDerivedInput(), 'dates.consultation_agreement_date'));
    }

    public function test_contract_12m_layout_generates_nine_documents_in_lawyer_order(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
            'client' => [
                'city' => 'Пловдив',
            ],
            'financial' => array_merge($this->batchInput()['financial'], [
                'credit_count_in_institutions' => 2,
                'credit_count_in_banks' => 1,
                'total_loan_amount_eur' => 12000,
                'commission_eur' => 600,
                'monthly_payments_eur' => 410,
                'net_income_eur' => 2200,
            ]),
            'selected_document_types' => [],
        ]), $operator);

        $this->assertSame(ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M, $batch->document_layout);

        $this->assertSame([
            ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
            ContractBatch::DOCUMENT_TYPE_DECLARATION,
        ], $batch->selected_document_types);

        $documentKeys = array_column($batch->generated_documents ?? [], 'document_key');

        $this->assertSame([
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION, 1),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION, 2),
            ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_DECLARATION),
        ], $documentKeys);

        $this->assertCount(9, $batch->generated_documents ?? []);

        $combinedDocxPath = Storage::disk('legal')->path($batch->combined_docx_path);
        $this->assertFileExists($combinedDocxPath);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($combinedDocxPath) === true);
        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($documentXml);
        preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $documentXml, $matches);
        $joined = implode(' ', $matches[1]);

        $positions = [
            'molba' => mb_strpos($joined, 'МОЛБА'),
            'consultation_12m' => mb_strpos($joined, 'ДОГОВОР ЗА ФИНАНСОВА КОНСУЛТАЦИЯ'),
            'consultation_protocol' => mb_strpos($joined, 'ПРОТОКОЛ ЗА ИЗВЪРШЕНА КОНСУЛТАЦИЯ'),
            'mediation_agreement' => mb_strpos($joined, 'ДОГОВОР ЗА ПОСРЕДНИЧЕСТВО Днес'),
            'mediation_protocol' => mb_strpos($joined, 'ПРИЕМО'),
        ];

        foreach ($positions as $label => $pos) {
            $this->assertNotFalse($pos, "Combined DOCX is missing section: {$label}");
        }

        $this->assertLessThan($positions['consultation_12m'], $positions['molba'] + 1);
        $this->assertLessThan($positions['consultation_protocol'], $positions['consultation_12m']);
        $this->assertLessThan($positions['mediation_agreement'], $positions['consultation_protocol']);
        $this->assertLessThan($positions['mediation_protocol'], $positions['mediation_agreement']);
    }

    public function test_combined_docx_footer_numbers_each_contract_independently(): void
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

        $combinedDocxPath = Storage::disk('legal')->path($batch->combined_docx_path);
        $this->assertFileExists($combinedDocxPath);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($combinedDocxPath) === true);

        $footerXml = '';
        $footerCount = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name === false || ! preg_match('#^word/footer\d+\.xml$#', $name)) {
                continue;
            }

            $footerCount++;
            $footerXml .= (string) $zip->getFromIndex($index);
        }

        $zip->close();

        $this->assertGreaterThan(0, $footerCount, 'Combined DOCX is missing footer parts.');
        $this->assertStringContainsString('SECTIONPAGES', $footerXml);
        $this->assertStringNotContainsString('NUMPAGES', $footerXml);
        $this->assertStringContainsString('PAGE', $footerXml);
    }

    public function test_company_promissory_note_fits_on_single_pdf_page_with_co_applicant(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'selected_document_types' => [
                ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ],
            'dates' => [
                'request_date' => '2026-03-20',
                'company_promissory_note_due_date' => '2026-05-12',
            ],
        ]), $operator);

        $this->assertNotNull(data_get($batch->getSubmittedInput(), 'co_applicant.full_name'));
        $this->assertTrue($batch->combinedPdfExists());

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => storage_path('app/private/mpdf-temp'),
        ]);

        $pageCount = $mpdf->setSourceFile(Storage::disk('legal')->path($batch->combined_pdf_path));

        $this->assertSame(
            1,
            $pageCount,
            'Записът на заповед за комисионна трябва да се събира на една страница.',
        );
    }

    public function test_it_defaults_to_the_suitable_guarantor_when_multiple_exist(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = $this->leadWithGuarantors($operator);

        $lead->guarantors()->create([
            'first_name' => 'Първи', 'last_name' => 'Поръчител',
            'egn' => '8101010000', 'phone' => '0888000001',
            'status' => LeadGuarantor::STATUS_UNSUITABLE,
        ]);

        $suitable = $lead->guarantors()->create([
            'first_name' => 'Мария', 'middle_name' => 'Петрова', 'last_name' => 'Годна',
            'egn' => '8202020000', 'phone' => '0888000002',
            'city' => 'София', 'email' => 'maria@example.com',
            'status' => LeadGuarantor::STATUS_SUITABLE,
        ]);

        $prefill = app(ContractGenerationService::class)
            ->buildFormPrefillFromLead($lead->fresh('guarantors'));

        $this->assertSame($suitable->id, $prefill['lead_guarantor_id']);
        $this->assertSame('Мария Петрова Годна', data_get($prefill, 'co_applicant.full_name'));
        $this->assertSame('София', data_get($prefill, 'co_applicant.permanent_address'));
    }

    public function test_it_does_not_default_a_guarantor_when_none_or_many_are_suitable(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = $this->leadWithGuarantors($operator);

        $lead->guarantors()->create([
            'first_name' => 'Първи', 'last_name' => 'Годен',
            'egn' => '8101010000', 'phone' => '0888000001',
            'status' => LeadGuarantor::STATUS_SUITABLE,
        ]);

        $lead->guarantors()->create([
            'first_name' => 'Втори', 'last_name' => 'Годен',
            'egn' => '8202020000', 'phone' => '0888000002',
            'status' => LeadGuarantor::STATUS_SUITABLE,
        ]);

        $prefill = app(ContractGenerationService::class)
            ->buildFormPrefillFromLead($lead->fresh('guarantors'));

        $this->assertNull($prefill['lead_guarantor_id']);
        $this->assertNull(data_get($prefill, 'co_applicant.full_name'));
    }

    public function test_it_exposes_guarantor_select_options_and_prefill_by_id(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = $this->leadWithGuarantors($operator);

        $first = $lead->guarantors()->create([
            'first_name' => 'Първи', 'last_name' => 'Поръчител',
            'egn' => '8101010000', 'phone' => '0888000001',
            'city' => 'Пловдив', 'email' => 'first@example.com',
            'status' => LeadGuarantor::STATUS_UNSUITABLE,
        ]);

        $second = $lead->guarantors()->create([
            'first_name' => 'Втори', 'last_name' => 'Поръчител',
            'egn' => '8202020000', 'phone' => '0888000002',
            'status' => LeadGuarantor::STATUS_SUITABLE,
        ]);

        $service = app(ContractGenerationService::class);

        $this->assertSame(2, $service->countLeadGuarantors($lead->id));

        $options = $service->guarantorSelectOptions($lead->id);
        $this->assertCount(2, $options);
        $this->assertSame('Първи Поръчител — Негоден', $options[$first->id]);
        $this->assertSame('Втори Поръчител — Годен', $options[$second->id]);

        $prefill = $service->buildCoApplicantPrefillForGuarantorId($lead->id, $first->id);
        $this->assertSame('Първи Поръчител', $prefill['full_name']);
        $this->assertSame('Пловдив', $prefill['permanent_address']);
        $this->assertNull($prefill['id_card_number']);

        $empty = $service->buildCoApplicantPrefillForGuarantorId($lead->id, null);
        $this->assertNull($empty['full_name']);
    }

    private function leadWithGuarantors(User $operator): Lead
    {
        return Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Иван', 'middle_name' => 'Петров', 'last_name' => 'Иванов',
            'egn' => '8501010000', 'phone' => '0888123456',
            'email' => 'ivan@example.com', 'city' => 'Пловдив',
            'salary' => 2600, 'credit_bank' => 'Test Bank', 'amount' => 12000,
            'status' => 'new', 'assigned_user_id' => $operator->id,
        ]);
    }

    public function test_it_requires_a_signing_city(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Попълнете град на подписване на договора.');

        app(ContractGenerationService::class)->createBatch($this->batchInput([
            'client' => [
                'city' => null,
            ],
        ]), $operator);
    }

    public function test_generated_documents_use_the_submitted_signing_city(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput([
            'client' => [
                'city' => 'София',
            ],
            'selected_document_types' => [
                ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
                ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
            ],
        ]), $operator);

        $combinedDocxPath = Storage::disk('legal')->path($batch->combined_docx_path);
        $this->assertFileExists($combinedDocxPath);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($combinedDocxPath) === true);
        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($documentXml);
        preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $documentXml, $matches);
        $joined = implode(' ', $matches[1]);

        $this->assertStringContainsString('в гр. София, се сключи настоящият договор за заем', $joined);
        $this->assertStringContainsString('гр. София, по банкова сметка', $joined);
        $this->assertStringContainsString('гр. София, 26.03.2026 г.', $joined);
        $this->assertStringNotContainsString('в гр. Пловдив', $joined);
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
    public function test_it_keeps_previous_generated_files_and_records_history_on_update(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput(), $operator);

        $previousDocumentPaths = $this->generatedVariantPaths($batch);
        $previousCombinedPdfPath = $batch->combined_pdf_path;
        $previousCombinedDocxPath = $batch->combined_docx_path;
        $previousGeneratedAt = $batch->generated_at;

        $this->assertNotEmpty($previousDocumentPaths);

        $updated = app(ContractGenerationService::class)->updateBatch($batch, $this->batchInput([
            'financial' => array_merge($this->batchInput()['financial'], [
                'fee_eur' => 300,
            ]),
        ]), $operator);

        foreach ($previousDocumentPaths as $path) {
            Storage::disk('legal')->assertExists($path);
        }

        Storage::disk('legal')->assertExists($previousCombinedPdfPath);
        Storage::disk('legal')->assertExists($previousCombinedDocxPath);

        $this->assertNotSame($previousCombinedPdfPath, $updated->combined_pdf_path);
        Storage::disk('legal')->assertExists($updated->combined_pdf_path);
        Storage::disk('legal')->assertExists($updated->combined_docx_path);

        $history = $updated->generated_document_history ?? [];

        $this->assertCount(1, $history);
        $this->assertSame($previousCombinedPdfPath, $history[0]['combined_pdf_path']);
        $this->assertSame($previousCombinedDocxPath, $history[0]['combined_docx_path']);
        $this->assertSame($previousGeneratedAt->toIso8601String(), $history[0]['generated_at']);
        $this->assertNotEmpty($history[0]['generated_documents']);

        $historyFile = $updated->findHistoryFile(0, ContractBatch::HISTORY_FILE_COMBINED_PDF);

        $this->assertNotNull($historyFile);
        $this->assertSame($previousCombinedPdfPath, $historyFile['path']);

        $secondUpdate = app(ContractGenerationService::class)->updateBatch($updated, $this->batchInput([
            'financial' => array_merge($this->batchInput()['financial'], [
                'fee_eur' => 350,
            ]),
        ]), $operator);

        $this->assertCount(2, $secondUpdate->generated_document_history ?? []);

        $display = $secondUpdate->getGeneratedDocumentHistoryForDisplay();

        $this->assertCount(2, $display);
        $this->assertSame(1, $display[0]['version']);
        $this->assertSame(0, $display[1]['version']);
        $this->assertTrue($display[1]['combined_pdf_available']);
    }

    public function test_it_deletes_current_and_history_files_when_batch_is_deleted(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = app(ContractGenerationService::class)->createBatch($this->batchInput(), $operator);

        $previousPaths = [
            ...$this->generatedVariantPaths($batch),
            $batch->combined_pdf_path,
            $batch->combined_docx_path,
        ];

        $updated = app(ContractGenerationService::class)->updateBatch($batch, $this->batchInput(), $operator);

        $allPaths = array_filter([
            ...$previousPaths,
            ...$this->generatedVariantPaths($updated),
            $updated->combined_pdf_path,
            $updated->combined_docx_path,
            $updated->archive_path,
        ]);

        foreach ($allPaths as $path) {
            Storage::disk('legal')->assertExists($path);
        }

        $updated->delete();

        foreach ($allPaths as $path) {
            Storage::disk('legal')->assertMissing($path);
        }
    }

    public function test_it_prefills_all_fields_from_latest_contract_of_the_lead(): void
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

        $guarantor = $lead->guarantors()->create([
            'first_name' => 'Maria',
            'middle_name' => 'Petrova',
            'last_name' => 'Ivanova',
            'egn' => '8602020000',
            'phone' => '0888999999',
            'email' => 'maria@example.com',
            'city' => 'Sofia',
        ]);

        app(ContractGenerationService::class)->saveDraftBatch([
            'lead_id' => $lead->id,
            'lead_guarantor_id' => $guarantor->id,
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_FULL,
            'client' => [
                'full_name' => 'Иван Петров Иванов',
                'egn' => '8501010000',
                'id_card_number' => '111222333',
                'id_card_issued_at' => '2020-05-20',
                'id_card_issued_by' => 'МВР Пловдив',
                'permanent_address' => 'гр. Пловдив, ул. Тест 1',
                'email' => null,
                'city' => 'Пловдив',
            ],
            'co_applicant' => [
                'full_name' => 'Мария Петрова Иванова',
                'egn' => '8602020000',
                'id_card_number' => '999888777',
                'id_card_issued_at' => '2021-06-18',
                'id_card_issued_by' => 'МВР София',
                'permanent_address' => 'гр. София, ул. Тест 2',
                'email' => null,
            ],
            'financial' => [
                'total_loan_amount_eur' => 30000,
                'commission_eur' => 2500,
            ],
            'loan' => [
                'institution_name' => 'Друга Банка',
            ],
            'dates' => [
                'request_date' => '2026-03-21',
            ],
        ], $operator);

        $prefill = app(ContractGenerationService::class)
            ->buildFormPrefillFromLead($lead->fresh('guarantors'));

        $this->assertSame(ContractBatch::DOCUMENT_LAYOUT_FULL, $prefill['document_layout']);
        $this->assertSame($guarantor->id, $prefill['lead_guarantor_id']);
        $this->assertSame('Иван Петров Иванов', data_get($prefill, 'client.full_name'));
        $this->assertSame('111222333', data_get($prefill, 'client.id_card_number'));
        $this->assertSame('2020-05-20', data_get($prefill, 'client.id_card_issued_at'));
        $this->assertSame('гр. Пловдив, ул. Тест 1', data_get($prefill, 'client.permanent_address'));
        $this->assertSame('Пловдив', data_get($prefill, 'client.city'));
        $this->assertSame('ivan@example.com', data_get($prefill, 'client.email'));
        $this->assertSame('999888777', data_get($prefill, 'co_applicant.id_card_number'));
        $this->assertSame('гр. София, ул. Тест 2', data_get($prefill, 'co_applicant.permanent_address'));
        $this->assertSame('maria@example.com', data_get($prefill, 'co_applicant.email'));
        $this->assertEquals(30000, data_get($prefill, 'financial.total_loan_amount_eur'));
        $this->assertEquals(2500, data_get($prefill, 'financial.commission_eur'));
        $this->assertEquals(2600, data_get($prefill, 'financial.monthly_net_income_eur'));
        $this->assertSame('Друга Банка', data_get($prefill, 'loan.institution_name'));
        $this->assertSame('2026-03-21', data_get($prefill, 'dates.request_date'));

        app(ContractGenerationService::class)->saveDraftBatch([
            'lead_id' => $lead->id,
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
            'client' => [
                'full_name' => 'Иван Петров Иванов',
                'id_card_number' => '444555666',
                'city' => 'София',
            ],
        ], $operator);

        $latestPrefill = app(ContractGenerationService::class)
            ->buildFormPrefillFromLead($lead->fresh('guarantors'));

        $this->assertSame(ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED, $latestPrefill['document_layout']);
        $this->assertSame('444555666', data_get($latestPrefill, 'client.id_card_number'));
        $this->assertSame('София', data_get($latestPrefill, 'client.city'));
    }

    /**
     * @return array<int, string>
     */
    private function generatedVariantPaths(ContractBatch $batch): array
    {
        $paths = [];

        foreach ($batch->generated_documents ?? [] as $document) {
            foreach (($document['variants'] ?? []) as $variant) {
                if (filled($variant['path'] ?? null)) {
                    $paths[] = $variant['path'];
                }
            }
        }

        return $paths;
    }

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
                'city' => 'Пловдив',
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
