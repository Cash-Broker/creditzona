<?php

namespace App\Console\Commands;

use App\Models\ContractBatch;
use App\Models\User;
use App\Services\Contracts\ContractGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateTestContractBatch extends Command
{
    protected $signature = 'contracts:generate-test {--layout=}';

    protected $description = 'Генерира тестов пакет договори с примерни данни';

    public function handle(ContractGenerationService $service): int
    {
        $operator = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orWhere('role', User::ROLE_OPERATOR)
            ->first();

        if (! $operator) {
            $this->error('Няма намерен потребител с роля admin или operator.');

            return self::FAILURE;
        }

        $layout = (string) ($this->option('layout') ?? '');
        $useContract12m = $layout === ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M;

        $input = [
            'company_key' => ContractBatch::COMPANY_D_CONSULTING_EOOD,
            'document_layout' => $useContract12m ? ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M : null,
            'client' => [
                'full_name' => 'Константин Константинов Павлов',
                'egn' => '0043077221',
                'id_card_number' => '2222222',
                'id_card_issued_at' => '2026-04-01',
                'id_card_issued_by' => 'МВР гр. Пловдив',
                'permanent_address' => 'гр. Пловдив, ул. Емил Зола № 9',
                'email' => 'konstantin@test.bg',
                'city' => 'Пловдив',
            ],
            'co_applicant' => [
                'full_name' => 'Венцислав Димчов Николов',
                'egn' => '9812287227',
                'id_card_number' => 'АА111111',
                'id_card_issued_at' => '2025-01-01',
                'id_card_issued_by' => 'МВР гр. София',
                'permanent_address' => 'гр. София, бул Витоша № 123',
                'email' => 'vencislav@test.bg',
            ],
            'financial' => [
                'active_credit_count' => 15,
                'liabilities_total_eur' => 35000,
                'monthly_repayment_burden_eur' => 1000,
                'monthly_net_income_eur' => 2800,
                'post_service_credit_count' => 1,
                'post_service_monthly_repayment_burden_eur' => 450,
                'fee_eur' => 1500,
                'co_applicant_promissory_note_amount_eur' => 12000,
                'loan_amount_eur' => 10000,
                'loan_return_amount_eur' => 12000,
                'loan_installment_eur' => 500,
                'credit_count_in_institutions' => 8,
                'credit_count_in_banks' => 7,
                'total_loan_amount_eur' => 35000,
                'commission_eur' => 1500,
                'monthly_payments_eur' => 1000,
                'net_income_eur' => 2800,
            ],
            'loan' => [
                'institution_name' => 'ИПОТЕХ СОФКОМ АД',
                'credit_agreement_number' => '013',
                'creditor_name' => 'ИПОТЕХ СОФКОМ АД',
            ],
            'dates' => [
                'request_date' => '2026-04-15',
                'mediation_contract_date' => '2026-04-15',
                'consultation_contract_date' => '2026-04-15',
                'consultation_protocol_date' => '2026-04-15',
                'company_promissory_note_issue_date' => '2026-04-15',
                'company_promissory_note_due_date' => '2026-07-15',
                'co_applicant_promissory_note_due_date' => '2028-04-15',
            ],
            'selected_document_types' => $useContract12m ? [] : [
                ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
                ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
                ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
                ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
                ContractBatch::DOCUMENT_TYPE_DECLARATION,
            ],
        ];

        $this->info('Генериране на тестов пакет договори...');

        $batch = $service->createBatch($input, $operator);

        $this->info('Пакетът е генериран успешно!');
        $this->newLine();

        if ($batch->combinedPdfExists()) {
            $pdfAbsolutePath = Storage::disk('legal')->path($batch->combined_pdf_path);
            $this->info("Комбиниран PDF: {$pdfAbsolutePath}");
        }

        if ($batch->archiveExists()) {
            $archivePath = Storage::disk('legal')->path($batch->archive_path);
            $this->info("ZIP архив: {$archivePath}");
        }

        $this->newLine();
        $this->info('Документи: '.count($batch->generated_documents ?? []).' бр.');
        $this->info("Batch ID: {$batch->id}");
        $this->info("Преглед в панела: /admin/contract-batches/{$batch->id}");

        return self::SUCCESS;
    }
}
