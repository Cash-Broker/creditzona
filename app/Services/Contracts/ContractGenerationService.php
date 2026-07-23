<?php

namespace App\Services\Contracts;

use App\Models\ContractBatch;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html as WordHtml;
use RuntimeException;
use Throwable;
use ZipArchive;

class ContractGenerationService
{
    /**
     * @var array<string, string>
     */
    private const DOCUMENT_VIEWS = [
        ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST => 'contracts.pdf.documents.application_request',
        ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT => 'contracts.pdf.documents.mediation_agreement',
        ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL => 'contracts.pdf.documents.mediation_protocol',
        ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT => 'contracts.pdf.documents.consultation_agreement',
        ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M => 'contracts.pdf.documents.consultation_agreement_12m',
        ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL => 'contracts.pdf.documents.consultation_protocol',
        ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE => 'contracts.pdf.documents.company_promissory_note',
        ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT => 'contracts.pdf.documents.loan_agreement',
        ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE => 'contracts.pdf.documents.co_applicant_promissory_note',
        ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION => 'contracts.pdf.documents.credit_history_declaration',
        ContractBatch::DOCUMENT_TYPE_DECLARATION => 'contracts.pdf.documents.declaration',
    ];

    /**
     * @var array<int, string>
     */
    private const FEE_DOCUMENT_TYPES = [
        ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
        ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
        ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M,
        ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
    ];

    /**
     * @var array<string, string>
     */
    private const WORD_CLASS_STYLES = [
        'title' => 'margin-bottom: 18px; font-size: 15pt; font-weight: bold; text-align: center; text-transform: uppercase;',
        'subtitle' => 'margin-bottom: 16px; font-size: 11pt; font-weight: bold; text-align: center;',
        'section-title' => 'margin-top: 16px; margin-bottom: 10px; font-weight: bold; text-align: center;',
        'signature-block' => 'margin-top: 22px;',
        'signature-row' => 'margin-top: 28px;',
        'small' => 'font-size: 9.8pt;',
        'spacer' => 'margin-bottom: 18px;',
    ];

    public function __construct(
        private readonly CurrencyFormatterService $currencyFormatter,
        private readonly WorkingDayService $workingDayService,
        private readonly BulgarianDateFormatterService $dateFormatter,
    ) {}

    /**
     * Префилва формата от запитването и — когато клиентът вече има договор —
     * допълва всички попълнени полета от последния му договор (памет на полетата).
     *
     * @return array<string, mixed>
     */
    public function buildFormPrefillFromLead(Lead $lead, ?LeadGuarantor $guarantor = null): array
    {
        $previousSubmitted = $this->resolveLatestContractBatchForLead($lead)?->getSubmittedInput() ?? [];

        $memory = $this->buildMemoryPrefill($previousSubmitted);
        $memoryCoApplicant = $memory['co_applicant'] ?? [];
        unset($memory['co_applicant']);

        $memoryGuarantor = $this->resolveMemoryGuarantor($lead, $previousSubmitted);

        if ($guarantor instanceof LeadGuarantor) {
            $selectedGuarantor = $this->resolveLeadGuarantor($lead, $guarantor);
            $applyMemoryCoApplicant = $selectedGuarantor !== null
                && $memoryGuarantor?->id === $selectedGuarantor->id;
        } elseif ($memoryCoApplicant !== [] || $memoryGuarantor instanceof LeadGuarantor) {
            $selectedGuarantor = $memoryGuarantor;
            $applyMemoryCoApplicant = true;
        } else {
            $selectedGuarantor = $this->resolveDefaultLeadGuarantor($lead);
            $applyMemoryCoApplicant = false;
        }

        $coApplicant = $this->buildPartyPrefillFromGuarantor($selectedGuarantor);

        if ($applyMemoryCoApplicant) {
            $coApplicant = array_replace($coApplicant, $memoryCoApplicant);
        }

        $requestDate = $this->buildRequestDateFromLead($lead)
            ?? CarbonImmutable::now('Europe/Sofia')->format('Y-m-d');

        $clientPrefill = $this->buildPartyPrefillFromLead($lead);

        $prefill = [
            'lead_id' => $lead->id,
            'lead_guarantor_id' => $selectedGuarantor?->id,
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_LOAN_ONLY,
            'client' => $clientPrefill,
            'co_applicant' => $coApplicant,
            'financial' => array_filter([
                'monthly_net_income_eur' => $lead->salary,
                'loan_amount_eur' => $lead->amount,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            'loan' => array_filter([
                'institution_name' => $lead->credit_bank,
                'creditor_name' => $lead->credit_bank,
            ], static fn (mixed $value): bool => filled($value)),
            'dates' => [
                'request_date' => $requestDate,
            ],
        ];

        return array_replace_recursive($prefill, $memory);
    }

    private function resolveLatestContractBatchForLead(Lead $lead): ?ContractBatch
    {
        return $lead->contractBatches()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Само попълнените стойности от последния договор, ограничени до секциите на формата.
     *
     * @param  array<string, mixed>  $previousSubmitted
     * @return array<string, mixed>
     */
    private function buildMemoryPrefill(array $previousSubmitted): array
    {
        $memory = array_intersect_key($previousSubmitted, array_flip([
            'document_layout',
            'company_key',
            'client',
            'co_applicant',
            'financial',
            'loan',
            'dates',
        ]));

        return $this->filterFilledRecursive($memory);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function filterFilledRecursive(array $values): array
    {
        $filtered = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $nested = $this->filterFilledRecursive($value);

                if ($nested !== []) {
                    $filtered[$key] = $nested;
                }

                continue;
            }

            if (filled($value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Поръчителят, избран в последния договор на запитването, ако още съществува.
     *
     * @param  array<string, mixed>  $previousSubmitted
     */
    private function resolveMemoryGuarantor(Lead $lead, array $previousSubmitted): ?LeadGuarantor
    {
        $guarantorId = $previousSubmitted['lead_guarantor_id'] ?? null;

        if (! is_numeric($guarantorId)) {
            return null;
        }

        $lead->loadMissing('guarantors');

        return $lead->guarantors->firstWhere('id', (int) $guarantorId);
    }

    /**
     * @return array<string, string|null>
     */
    public function buildCoApplicantPrefillFromGuarantor(?LeadGuarantor $guarantor): array
    {
        return $this->buildPartyPrefillFromGuarantor($guarantor);
    }

    /**
     * Опции за падащия списък с поръчителите на дадено запитване (id => етикет с име и статус).
     *
     * @return array<int, string>
     */
    public function guarantorSelectOptions(mixed $leadId): array
    {
        $lead = $this->resolveLeadById($leadId);

        if (! $lead instanceof Lead) {
            return [];
        }

        return $lead->guarantors
            ->sortBy('id')
            ->mapWithKeys(fn (LeadGuarantor $guarantor): array => [
                $guarantor->id => $this->buildGuarantorOptionLabel($guarantor),
            ])
            ->all();
    }

    public function countLeadGuarantors(mixed $leadId): int
    {
        $lead = $this->resolveLeadById($leadId);

        return $lead instanceof Lead ? $lead->guarantors->count() : 0;
    }

    /**
     * Попълва данните на авалиста от конкретно избран поръчител по неговото id.
     * Празните полета на личната карта се изчистват, тъй като те не идват от запитването.
     *
     * @return array<string, string|null>
     */
    public function buildCoApplicantPrefillForGuarantorId(mixed $leadId, mixed $guarantorId): array
    {
        if (! is_numeric($guarantorId)) {
            return $this->buildPartyPrefillFromGuarantor(null);
        }

        $lead = $this->resolveLeadById($leadId);
        $guarantor = $lead?->guarantors->firstWhere('id', (int) $guarantorId);

        return $this->buildPartyPrefillFromGuarantor($guarantor);
    }

    public function createBatch(array $input, User $actor): ContractBatch
    {
        return $this->persistBatch(null, $input, $actor);
    }

    public function updateBatch(ContractBatch $batch, array $input, User $actor): ContractBatch
    {
        return $this->persistBatch($batch, $input, $actor);
    }

    /**
     * Persist Step 1 (basic) data without running validation or document generation.
     * Used by the two-step contract creation flow before the user fills out type-specific data.
     */
    public function saveDraftBatch(array $input, User $actor, ?ContractBatch $existing = null): ContractBatch
    {
        $timezone = 'Europe/Sofia';
        $documentLayout = $this->normalizeText($input['document_layout'] ?? null);
        $companyKey = $this->normalizeText($input['company_key'] ?? null) ?? ContractBatch::COMPANY_REKREDO_KONSULT_DPK;

        $clientFullName = $this->normalizeText(data_get($input, 'client.full_name')) ?? '';
        $coApplicantFullName = $this->normalizeText(data_get($input, 'co_applicant.full_name'));
        $clientCity = $this->normalizeText(data_get($input, 'client.city'));
        $requestDate = $this->normalizeDateString(data_get($input, 'dates.request_date'))
            ?? CarbonImmutable::now($timezone)->format('Y-m-d');

        return DB::transaction(function () use (
            $existing,
            $input,
            $actor,
            $documentLayout,
            $companyKey,
            $clientFullName,
            $coApplicantFullName,
            $clientCity,
            $requestDate
        ): ContractBatch {
            $batch = $existing ?? new ContractBatch;

            $existingPayload = is_array($batch->input_payload ?? null) ? $batch->input_payload : [];
            $existingSubmitted = is_array($existingPayload['submitted'] ?? null) ? $existingPayload['submitted'] : [];
            $existingDerived = is_array($existingPayload['derived'] ?? null) ? $existingPayload['derived'] : [];

            $mergedSubmitted = array_replace_recursive($existingSubmitted, $input);

            $batch->fill([
                'company_key' => $companyKey,
                'document_layout' => $documentLayout,
                'client_full_name' => $clientFullName,
                'client_city' => $clientCity,
                'co_applicant_full_name' => $coApplicantFullName ?: null,
                'request_date' => $requestDate,
                'selected_document_types' => $batch->selected_document_types ?? [],
                'input_payload' => [
                    'submitted' => $mergedSubmitted,
                    'derived' => $existingDerived,
                ],
            ]);

            if (array_key_exists('lead_id', $input)) {
                $batch->lead_id = is_numeric($input['lead_id']) ? (int) $input['lead_id'] : null;
            }

            if (! $batch->exists) {
                $batch->created_by_user_id = $actor->id;

                if (blank($batch->attached_user_id)) {
                    $batch->attached_user_id = $actor->id;
                }
            }

            $batch->save();

            return $batch->fresh();
        });
    }

    private function persistBatch(?ContractBatch $existingBatch, array $input, User $actor): ContractBatch
    {
        $submitted = $this->normalizeInput($input);
        $derived = $this->buildDerivedData($submitted);
        $directoryKey = (string) Str::uuid();

        $generatedDocuments = [];
        $combinedPdf = null;
        $combinedDocx = null;
        $archive = null;
        $previousHistory = $existingBatch?->generated_document_history ?? [];
        $previousSnapshot = $existingBatch === null ? null : [
            'generated_at' => $existingBatch->generated_at?->toIso8601String(),
            'generated_documents' => $existingBatch->generated_documents ?? [],
            'combined_pdf_path' => $existingBatch->combined_pdf_path,
            'combined_pdf_file_name' => $existingBatch->combined_pdf_file_name,
            'combined_docx_path' => $existingBatch->combined_docx_path,
            'combined_docx_file_name' => $existingBatch->combined_docx_file_name,
            'archive_path' => $existingBatch->archive_path,
            'archive_file_name' => $existingBatch->archive_file_name,
        ];

        try {
            $generatedDocuments = $this->generateDocuments(
                $submitted['selected_document_types'],
                $submitted,
                $derived,
                $directoryKey,
            );

            $layoutForOrder = is_string($submitted['document_layout'] ?? null) ? $submitted['document_layout'] : null;

            $combinedPdf = $this->generateCombinedPdf(
                $submitted['selected_document_types'],
                $submitted,
                $derived,
                $directoryKey,
                $submitted['client']['full_name'],
                $layoutForOrder,
            );

            $combinedDocx = $this->generateCombinedDocx(
                $submitted['selected_document_types'],
                $submitted,
                $derived,
                $directoryKey,
                $submitted['client']['full_name'],
                $layoutForOrder,
            );

            $archive = $this->createArchive($generatedDocuments, $combinedPdf, $directoryKey, $submitted['client']['full_name']);

            $batch = DB::transaction(function () use (
                $existingBatch,
                $submitted,
                $derived,
                $generatedDocuments,
                $combinedPdf,
                $combinedDocx,
                $archive,
                $previousHistory,
                $previousSnapshot,
                $actor
            ): ContractBatch {
                $batch = $existingBatch ?? new ContractBatch;

                $batch->fill([
                    'lead_id' => $submitted['lead_id'],
                    'company_key' => $submitted['company_key'],
                    'document_layout' => $submitted['document_layout'] ?? null,
                    'client_full_name' => $submitted['client']['full_name'],
                    'client_city' => $submitted['client']['city'] ?? null,
                    'co_applicant_full_name' => $submitted['co_applicant']['full_name'] ?: null,
                    'request_date' => $submitted['dates']['request_date'],
                    'selected_document_types' => $submitted['selected_document_types'],
                    'input_payload' => [
                        'submitted' => $submitted,
                        'derived' => $derived,
                    ],
                    'generated_documents' => $generatedDocuments,
                    'generated_document_history' => $this->appendGeneratedDocumentHistory($previousHistory, $previousSnapshot),
                    'combined_pdf_path' => $combinedPdf['path'] ?? null,
                    'combined_pdf_file_name' => $combinedPdf['download_name'] ?? null,
                    'combined_docx_path' => $combinedDocx['path'] ?? null,
                    'combined_docx_file_name' => $combinedDocx['download_name'] ?? null,
                    'archive_path' => $archive['path'] ?? null,
                    'archive_file_name' => $archive['download_name'] ?? null,
                    'generated_at' => now(),
                ]);

                if (! $batch->exists) {
                    $batch->created_by_user_id = $actor->id;

                    if (blank($batch->attached_user_id)) {
                        $batch->attached_user_id = $actor->id;
                    }
                }

                $batch->save();

                return $batch->fresh();
            });

            return $batch;
        } catch (Throwable $throwable) {
            $this->deleteGeneratedSnapshot($generatedDocuments, $combinedPdf['path'] ?? null, $combinedDocx['path'] ?? null, $archive['path'] ?? null);

            throw $throwable;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        $companyKey = $this->normalizeText($input['company_key'] ?? null);
        $documentLayout = $this->normalizeText($input['document_layout'] ?? null);
        $layoutOptions = ContractBatch::getLayoutOptions();

        if ($documentLayout !== null && array_key_exists($documentLayout, $layoutOptions)) {
            $selectedDocumentTypes = ContractBatch::orderSelectedDocumentTypes(
                ContractBatch::getDocumentTypesForLayout($documentLayout),
                $documentLayout,
            );
        } else {
            $documentLayout = null;
            $selectedDocumentTypes = ContractBatch::orderSelectedDocumentTypes($input['selected_document_types'] ?? []);
        }

        $sourceLead = $this->resolveLeadById($input['lead_id'] ?? null);
        $sourceGuarantor = $this->resolveLeadGuarantorById($sourceLead, $input['lead_guarantor_id'] ?? null);

        if ($companyKey === null || ContractBatch::getCompanyData($companyKey) === []) {
            throw new RuntimeException('Изберете валидна фирма.');
        }

        if ($selectedDocumentTypes === []) {
            throw new RuntimeException('Изберете поне един документ за генериране.');
        }

        $client = $this->fillMissingPartyValues(
            $this->normalizePartyInput($input['client'] ?? []),
            $sourceLead ? $this->buildPartyPrefillFromLead($sourceLead) : [],
        );

        $coApplicant = $this->fillMissingPartyValues(
            $this->normalizePartyInput($input['co_applicant'] ?? []),
            $sourceGuarantor ? $this->buildPartyPrefillFromGuarantor($sourceGuarantor) : [],
        );

        $clientCity = $this->normalizeText(data_get($input, 'client.city') ?? data_get($input, 'client_city'));

        $submitted = [
            'lead_id' => $sourceLead?->id,
            'lead_guarantor_id' => $sourceGuarantor?->id,
            'company_key' => $companyKey,
            'document_layout' => $documentLayout,
            'client' => array_merge($client, ['city' => $clientCity]),
            'co_applicant' => $coApplicant,
            'financial' => [
                'active_credit_count' => $this->normalizeInteger(data_get($input, 'financial.active_credit_count')),
                'liabilities_total_eur' => $this->normalizeAmount(data_get($input, 'financial.liabilities_total_eur')),
                'monthly_repayment_burden_eur' => $this->normalizeAmount(data_get($input, 'financial.monthly_repayment_burden_eur')),
                'monthly_net_income_eur' => $this->normalizeAmount(data_get($input, 'financial.monthly_net_income_eur'))
                    ?? $this->normalizeAmount($sourceLead?->salary),
                'post_service_credit_count' => $this->normalizeInteger(data_get($input, 'financial.post_service_credit_count')),
                'post_service_monthly_repayment_burden_eur' => $this->normalizeAmount(data_get($input, 'financial.post_service_monthly_repayment_burden_eur')),
                'fee_eur' => $this->normalizeAmount(data_get($input, 'financial.fee_eur')),
                'company_promissory_note_amount_eur' => $this->normalizeAmount(data_get($input, 'financial.company_promissory_note_amount_eur')),
                'co_applicant_promissory_note_amount_eur' => $this->normalizeAmount(data_get($input, 'financial.co_applicant_promissory_note_amount_eur')),
                'loan_amount_eur' => $this->normalizeAmount(data_get($input, 'financial.loan_amount_eur'))
                    ?? $this->normalizeAmount($sourceLead?->amount),
                'loan_return_amount_eur' => $this->normalizeAmount(data_get($input, 'financial.loan_return_amount_eur')),
                'loan_installment_eur' => $this->normalizeAmount(data_get($input, 'financial.loan_installment_eur')),
                'loan_installment_day_of_month' => $this->normalizeDayOfMonth(data_get($input, 'financial.loan_installment_day_of_month')),
                'credit_count_in_institutions' => $this->normalizeInteger(data_get($input, 'financial.credit_count_in_institutions')),
                'institution_count' => $this->normalizeInteger(data_get($input, 'financial.institution_count')),
                'credit_count_in_banks' => $this->normalizeInteger(data_get($input, 'financial.credit_count_in_banks')),
                'bank_count' => $this->normalizeInteger(data_get($input, 'financial.bank_count')),
                'total_loan_amount_eur' => $this->normalizeAmount(data_get($input, 'financial.total_loan_amount_eur')),
                'commission_eur' => $this->normalizeAmount(data_get($input, 'financial.commission_eur')),
                'monthly_payments_eur' => $this->normalizeAmount(data_get($input, 'financial.monthly_payments_eur')),
                'private_loans_eur' => $this->normalizeAmount(data_get($input, 'financial.private_loans_eur')),
                'net_income_eur' => $this->normalizeAmount(data_get($input, 'financial.net_income_eur')),
                'court_required_eur' => $this->normalizeAmount(data_get($input, 'financial.court_required_eur')),
            ],
            'loan' => [
                'institution_name' => $this->normalizeText(data_get($input, 'loan.institution_name'))
                    ?? $this->normalizeText($sourceLead?->credit_bank),
                'credit_agreement_number' => $this->normalizeText(data_get($input, 'loan.credit_agreement_number')),
                'creditor_name' => $this->normalizeText(data_get($input, 'loan.creditor_name'))
                    ?? $this->normalizeText($sourceLead?->credit_bank),
            ],
            'dates' => $this->resolveDateInputs([
                'request_date' => $this->normalizeDateString(data_get($input, 'dates.request_date'))
                    ?? $this->buildRequestDateFromLead($sourceLead),
                'mediation_contract_date' => $this->normalizeDateString(data_get($input, 'dates.mediation_contract_date')),
                'mediation_protocol_date' => $this->normalizeDateString(data_get($input, 'dates.mediation_protocol_date')),
                'consultation_contract_date' => $this->normalizeDateString(data_get($input, 'dates.consultation_contract_date')),
                'consultation_protocol_date' => $this->normalizeDateString(data_get($input, 'dates.consultation_protocol_date')),
                'company_promissory_note_issue_date' => $this->normalizeDateString(data_get($input, 'dates.company_promissory_note_issue_date')),
                'company_promissory_note_due_date' => $this->normalizeDateString(data_get($input, 'dates.company_promissory_note_due_date')),
                'loan_agreement_date' => $this->normalizeDateString(data_get($input, 'dates.loan_agreement_date')),
                'loan_due_date' => $this->normalizeDateString(data_get($input, 'dates.loan_due_date')),
                'loan_last_installment_date' => $this->normalizeDateString(data_get($input, 'dates.loan_last_installment_date')),
                'co_applicant_promissory_note_issue_date' => $this->normalizeDateString(data_get($input, 'dates.co_applicant_promissory_note_issue_date')),
                'co_applicant_promissory_note_due_date' => $this->normalizeDateString(data_get($input, 'dates.co_applicant_promissory_note_due_date')),
                'declaration_date' => $this->normalizeDateString(data_get($input, 'dates.declaration_date')),
            ]),
            'selected_document_types' => $selectedDocumentTypes,
        ];

        $this->applyLayoutFieldMappings($submitted);

        $this->validateSubmittedInput($submitted);

        return $submitted;
    }

    /**
     * Auto-map Step 1 credit-data fields to legacy financial keys when the layout
     * does not expose them on Step 2. Keeps the validateSubmittedInput contract intact.
     *
     * @param  array<string, mixed>  $submitted
     */
    private function applyLayoutFieldMappings(array &$submitted): void
    {
        $layout = $submitted['document_layout'] ?? null;

        if ($layout === null) {
            return;
        }

        $financial = &$submitted['financial'];

        // Total liabilities → from "Общ Размер"
        if ($financial['liabilities_total_eur'] === null && $financial['total_loan_amount_eur'] !== null) {
            $financial['liabilities_total_eur'] = $financial['total_loan_amount_eur'];
        }

        // Monthly repayment burden → from "Месечни Вноски"
        if ($financial['monthly_repayment_burden_eur'] === null && $financial['monthly_payments_eur'] !== null) {
            $financial['monthly_repayment_burden_eur'] = $financial['monthly_payments_eur'];
        }

        // Monthly net income → from "Доход (Нетно)". The operator-entered Step 1 value must
        // override the lead-salary fallback applied in normalizeInput(); otherwise the lead's
        // salary silently wins and the operator can never correct it from the form.
        if ($financial['net_income_eur'] !== null) {
            $financial['monthly_net_income_eur'] = $financial['net_income_eur'];
        }

        // Active credit count → sum of "Кредити в институции" + "Кредити в банки"
        if ($financial['active_credit_count'] === null) {
            $institutions = $financial['credit_count_in_institutions'];
            $banks = $financial['credit_count_in_banks'];

            if ($institutions !== null || $banks !== null) {
                $financial['active_credit_count'] = ($institutions ?? 0) + ($banks ?? 0);
            }
        }

        // Fee → from "Комисионна" when not explicitly provided
        if ($financial['fee_eur'] === null && $financial['commission_eur'] !== null) {
            $financial['fee_eur'] = $financial['commission_eur'];
        }

        $layoutsWithoutLoanCard = [
            ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
            ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR,
            ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
        ];

        // Post-service data (consultation/mediation protocol) — sensible defaults
        // After the service the client typically has fewer credits and one consolidated payment.
        if ($financial['post_service_credit_count'] === null && in_array($layout, $layoutsWithoutLoanCard, true)) {
            $financial['post_service_credit_count'] = 1;
        }

        if ($financial['post_service_monthly_repayment_burden_eur'] === null
            && in_array($layout, $layoutsWithoutLoanCard, true)) {
            $financial['post_service_monthly_repayment_burden_eur'] = $financial['loan_installment_eur']
                ?? $financial['monthly_payments_eur'];
        }

        // When the loan card isn't shown, the loan amount comes from "Общ Размер". As with the
        // income above, the operator's Step 1 value must override the lead-amount fallback baked
        // into normalizeInput() so it can actually be corrected from the form.
        if (in_array($layout, $layoutsWithoutLoanCard, true)) {
            if ($financial['total_loan_amount_eur'] !== null) {
                $financial['loan_amount_eur'] = $financial['total_loan_amount_eur'];
            }
        }

        // mediation_protocol's credit_agreement_number is left blank-friendly
        if (blank($submitted['loan']['credit_agreement_number'] ?? null)) {
            $submitted['loan']['credit_agreement_number'] = '—';
        }

        // co_applicant_promissory_note auto-derive (form does not expose these fields)
        if ($financial['co_applicant_promissory_note_amount_eur'] === null && $financial['loan_return_amount_eur'] !== null) {
            $financial['co_applicant_promissory_note_amount_eur'] = $financial['loan_return_amount_eur'];
        }

        if (blank($submitted['dates']['co_applicant_promissory_note_due_date'] ?? null)
            && filled($submitted['dates']['loan_last_installment_date'] ?? null)) {
            $submitted['dates']['co_applicant_promissory_note_due_date'] = $submitted['dates']['loan_last_installment_date'];
        }
    }

    /**
     * @param  array<string, string|null>  $dates
     * @return array<string, string|null>
     */
    private function resolveDateInputs(array $dates): array
    {
        $timezone = 'Europe/Sofia';
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $loanAgreementDate = filled($dates['loan_agreement_date'] ?? null)
            ? CarbonImmutable::parse($dates['loan_agreement_date'], $timezone)->startOfDay()
            : $today;

        $requestDate = $dates['request_date']
            ?? ($dates['consultation_contract_date'] ?? null)
            ?? ($dates['loan_agreement_date'] ?? null)
            ?? $today->format('Y-m-d');

        $mediationContractDate = $dates['mediation_contract_date']
            ?? ($dates['consultation_contract_date'] ?? null);

        return [
            'request_date' => $requestDate,
            'mediation_contract_date' => $mediationContractDate,
            'mediation_protocol_date' => filled($dates['mediation_protocol_date'] ?? null)
                ? CarbonImmutable::parse($dates['mediation_protocol_date'], $timezone)->format('Y-m-d')
                : $today->format('Y-m-d'),
            'consultation_contract_date' => $dates['consultation_contract_date'] ?? null,
            'consultation_protocol_date' => filled($dates['consultation_protocol_date'] ?? null)
                ? CarbonImmutable::parse($dates['consultation_protocol_date'], $timezone)->format('Y-m-d')
                : $this->workingDayService->subtractWorkingDays($today, 2, $timezone)->format('Y-m-d'),
            'company_promissory_note_issue_date' => filled($dates['company_promissory_note_issue_date'] ?? null)
                ? CarbonImmutable::parse($dates['company_promissory_note_issue_date'], $timezone)->format('Y-m-d')
                : $today->format('Y-m-d'),
            'company_promissory_note_due_date' => $dates['company_promissory_note_due_date'] ?? null,
            'loan_agreement_date' => $loanAgreementDate->format('Y-m-d'),
            'loan_due_date' => filled($dates['loan_due_date'] ?? null)
                ? CarbonImmutable::parse($dates['loan_due_date'], $timezone)->format('Y-m-d')
                : $loanAgreementDate->addYears(2)->format('Y-m-d'),
            'loan_last_installment_date' => $dates['loan_last_installment_date'] ?? null,
            'co_applicant_promissory_note_issue_date' => filled($dates['co_applicant_promissory_note_issue_date'] ?? null)
                ? CarbonImmutable::parse($dates['co_applicant_promissory_note_issue_date'], $timezone)->format('Y-m-d')
                : $today->format('Y-m-d'),
            'co_applicant_promissory_note_due_date' => $dates['co_applicant_promissory_note_due_date'] ?? null,
            'declaration_date' => filled($dates['declaration_date'] ?? null)
                ? CarbonImmutable::parse($dates['declaration_date'], $timezone)->format('Y-m-d')
                : $today->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizePartyInput(array $party): array
    {
        return [
            'full_name' => $this->normalizeText($party['full_name'] ?? null),
            'egn' => $this->normalizeText($party['egn'] ?? null),
            'id_card_number' => $this->normalizeText($party['id_card_number'] ?? null),
            'id_card_issued_at' => $this->normalizeDateString($party['id_card_issued_at'] ?? null),
            'id_card_issued_by' => $this->normalizeText($party['id_card_issued_by'] ?? null),
            'permanent_address' => $this->normalizeText($party['permanent_address'] ?? null),
            'email' => $this->normalizeText($party['email'] ?? null),
        ];
    }

    /**
     * @param  array<string, string|null>  $party
     * @param  array<string, string|null>  $defaults
     * @return array<string, string|null>
     */
    private function fillMissingPartyValues(array $party, array $defaults): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $party) || $party[$key] !== null) {
                continue;
            }

            $party[$key] = $value;
        }

        return $party;
    }

    private function resolveLeadById(mixed $leadId): ?Lead
    {
        if (! is_numeric($leadId)) {
            return null;
        }

        $normalizedLeadId = (int) $leadId;

        if ($normalizedLeadId < 1) {
            return null;
        }

        return Lead::query()
            ->with('guarantors')
            ->find($normalizedLeadId);
    }

    private function resolveLeadGuarantor(Lead $lead, ?LeadGuarantor $guarantor = null): ?LeadGuarantor
    {
        $lead->loadMissing('guarantors');

        if ($guarantor instanceof LeadGuarantor && (int) $guarantor->lead_id === (int) $lead->id) {
            return $guarantor;
        }

        return $lead->guarantors
            ->sortBy('id')
            ->first();
    }

    /**
     * Поръчителят, който се избира по подразбиране при префилване на формата.
     * При един поръчител се ползва той. При няколко се ползва само ако има точно един
     * със статус „Годен"; иначе операторът избира ръчно от падащия списък.
     */
    private function resolveDefaultLeadGuarantor(Lead $lead): ?LeadGuarantor
    {
        $lead->loadMissing('guarantors');

        $guarantors = $lead->guarantors->sortBy('id')->values();

        if ($guarantors->count() <= 1) {
            return $guarantors->first();
        }

        $suitable = $guarantors
            ->where('status', LeadGuarantor::STATUS_SUITABLE)
            ->values();

        return $suitable->count() === 1 ? $suitable->first() : null;
    }

    private function buildGuarantorOptionLabel(LeadGuarantor $guarantor): string
    {
        $name = $this->buildPersonFullName(
            $guarantor->first_name,
            $guarantor->middle_name,
            $guarantor->last_name,
        ) ?? 'Поръчител #'.$guarantor->id;

        return $name.' — '.LeadGuarantor::getStatusLabel($guarantor->status);
    }

    private function resolveLeadGuarantorById(?Lead $lead, mixed $guarantorId): ?LeadGuarantor
    {
        if (! $lead instanceof Lead) {
            return null;
        }

        if (! is_numeric($guarantorId)) {
            return $this->resolveLeadGuarantor($lead);
        }

        $lead->loadMissing('guarantors');

        return $lead->guarantors
            ->firstWhere('id', (int) $guarantorId)
            ?? $this->resolveLeadGuarantor($lead);
    }

    /**
     * @return array<string, string|null>
     */
    private function buildPartyPrefillFromLead(Lead $lead): array
    {
        return [
            'full_name' => $this->buildPersonFullName(
                $lead->first_name,
                $lead->middle_name,
                $lead->last_name,
            ),
            'egn' => $this->normalizeText($lead->egn),
            'id_card_number' => null,
            'id_card_issued_at' => null,
            'id_card_issued_by' => null,
            'permanent_address' => $this->normalizeText($lead->city),
            'email' => $this->normalizeText($lead->email),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function buildPartyPrefillFromGuarantor(?LeadGuarantor $guarantor): array
    {
        if (! $guarantor instanceof LeadGuarantor) {
            return [
                'full_name' => null,
                'egn' => null,
                'id_card_number' => null,
                'id_card_issued_at' => null,
                'id_card_issued_by' => null,
                'permanent_address' => null,
                'email' => null,
            ];
        }

        return [
            'full_name' => $this->buildPersonFullName(
                $guarantor->first_name,
                $guarantor->middle_name,
                $guarantor->last_name,
            ),
            'egn' => $this->normalizeText($guarantor->egn),
            'id_card_number' => null,
            'id_card_issued_at' => null,
            'id_card_issued_by' => null,
            'permanent_address' => $this->normalizeText($guarantor->city),
            'email' => $this->normalizeText($guarantor->email),
        ];
    }

    private function buildPersonFullName(?string $firstName, ?string $middleName, ?string $lastName): ?string
    {
        $fullName = trim(implode(' ', array_filter([
            $this->normalizeText($firstName),
            $this->normalizeText($middleName),
            $this->normalizeText($lastName),
        ])));

        return filled($fullName) ? $fullName : null;
    }

    private function buildRequestDateFromLead(?Lead $lead): ?string
    {
        if (! $lead instanceof Lead || ! $lead->created_at) {
            return null;
        }

        return CarbonImmutable::parse($lead->created_at, 'Europe/Sofia')
            ->startOfDay()
            ->format('Y-m-d');
    }

    /**
     * @param  array<string, mixed>  $submitted
     */
    private function validateSubmittedInput(array $submitted): void
    {
        $this->ensurePartyIsComplete($submitted['client'], 'клиента');

        if (blank($submitted['client']['city'] ?? null)) {
            throw new RuntimeException('Попълнете град на подписване на договора.');
        }

        if ($this->requiresStrictCoApplicant($submitted['selected_document_types'])) {
            $this->ensurePartyIsComplete($submitted['co_applicant'], 'съкредитоискателя');
        }

        if ($submitted['dates']['request_date'] === null) {
            throw new RuntimeException('Попълнете дата на заявка.');
        }

        if ($this->requiresMediationContractDate($submitted['selected_document_types'])
            && $submitted['dates']['mediation_contract_date'] === null) {
            throw new RuntimeException('Попълнете дата на договор за посредничество.');
        }

        if ($this->requiresApplicationRequest($submitted['selected_document_types'])) {
            foreach ([
                'active_credit_count' => 'брой активни кредити',
                'liabilities_total_eur' => 'общ размер на задълженията',
                'monthly_repayment_burden_eur' => 'обща месечна погасителна тежест',
                'monthly_net_income_eur' => 'среден месечен нетен доход',
            ] as $key => $label) {
                if ($submitted['financial'][$key] === null) {
                    throw new RuntimeException("Попълнете {$label}.");
                }
            }
        }

        if ($this->requiresProtocolOutcomeData($submitted['selected_document_types'])) {
            foreach ([
                'active_credit_count' => 'брой кредити преди услугата',
                'monthly_repayment_burden_eur' => 'обща месечна вноска преди услугата',
                'post_service_credit_count' => 'брой кредити след услугата',
                'post_service_monthly_repayment_burden_eur' => 'месечна вноска след услугата',
            ] as $key => $label) {
                if ($submitted['financial'][$key] === null) {
                    throw new RuntimeException("Попълнете {$label}.");
                }
            }
        }

        if ($this->requiresFee($submitted['selected_document_types']) && $submitted['financial']['fee_eur'] === null) {
            throw new RuntimeException('Попълнете възнаграждението в евро.');
        }

        if (in_array(ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE, $submitted['selected_document_types'], true)) {
            if ($submitted['dates']['company_promissory_note_due_date'] === null) {
                throw new RuntimeException('Попълнете падеж на записа на заповед към фирмата.');
            }

            if ($this->isBridgeCreditLayout($submitted)
                && $submitted['financial']['company_promissory_note_amount_eur'] === null) {
                throw new RuntimeException('Попълнете сума на записа на заповед към фирмата.');
            }
        }

        if (in_array(ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE, $submitted['selected_document_types'], true)) {
            if ($submitted['financial']['co_applicant_promissory_note_amount_eur'] === null) {
                throw new RuntimeException('Попълнете сума на записа на заповед между клиента и съкредитоискателя.');
            }

            if ($submitted['dates']['co_applicant_promissory_note_due_date'] === null) {
                throw new RuntimeException('Попълнете падеж на записа на заповед между клиента и съкредитоискателя.');
            }
        }

        if (in_array(ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL, $submitted['selected_document_types'], true)
            && blank($submitted['loan']['credit_agreement_number'])) {
            throw new RuntimeException('Попълнете номер на договора за кредит.');
        }

        if ($this->requiresLoanFields($submitted['selected_document_types'])) {
            foreach ([
                'institution_name' => 'финансова институция или банка',
                'loan_amount_eur' => 'размер на кредита',
                'loan_return_amount_eur' => 'сума за връщане',
                'loan_installment_eur' => 'месечна вноска',
            ] as $key => $label) {
                $value = str_contains($key, '_eur')
                    ? $submitted['financial'][$key]
                    : $submitted['loan'][$key];

                if ($value === null) {
                    throw new RuntimeException("Попълнете {$label}.");
                }
            }
        }
    }

    /**
     * @param  array<string, string|null>  $party
     */
    private function ensurePartyIsComplete(array $party, string $label): void
    {
        foreach ([
            'full_name' => 'трите имена',
            'egn' => 'ЕГН',
            'id_card_number' => 'номер на лична карта',
            'id_card_issued_at' => 'дата на издаване на личната карта',
            'id_card_issued_by' => 'издадено от',
            'permanent_address' => 'постоянен адрес',
        ] as $key => $fieldLabel) {
            if (blank($party[$key] ?? null)) {
                throw new RuntimeException("Попълнете {$fieldLabel} за {$label}.");
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDerivedData(array $submitted): array
    {
        $timezone = 'Europe/Sofia';
        $company = ContractBatch::getCompanyData($submitted['company_key']);

        $requestDate = CarbonImmutable::parse($submitted['dates']['request_date'], $timezone)->startOfDay();
        $mediationContractDate = filled($submitted['dates']['mediation_contract_date'])
            ? CarbonImmutable::parse($submitted['dates']['mediation_contract_date'], $timezone)->startOfDay()
            : null;
        $mediationProtocolDate = CarbonImmutable::parse($submitted['dates']['mediation_protocol_date'], $timezone)->startOfDay();
        $consultationContractDate = filled($submitted['dates']['consultation_contract_date'] ?? null)
            ? CarbonImmutable::parse($submitted['dates']['consultation_contract_date'], $timezone)->startOfDay()
            : $requestDate;
        $consultationProtocolDate = CarbonImmutable::parse($submitted['dates']['consultation_protocol_date'], $timezone)->startOfDay();
        $companyPromissoryIssueDate = CarbonImmutable::parse($submitted['dates']['company_promissory_note_issue_date'], $timezone)->startOfDay();
        $companyPromissoryDueDate = filled($submitted['dates']['company_promissory_note_due_date'])
            ? CarbonImmutable::parse($submitted['dates']['company_promissory_note_due_date'], $timezone)->startOfDay()
            : null;
        $loanAgreementDate = CarbonImmutable::parse($submitted['dates']['loan_agreement_date'], $timezone)->startOfDay();
        $loanDueDate = CarbonImmutable::parse($submitted['dates']['loan_due_date'], $timezone)->startOfDay();
        $coApplicantPromissoryIssueDate = CarbonImmutable::parse($submitted['dates']['co_applicant_promissory_note_issue_date'], $timezone)->startOfDay();
        $coApplicantPromissoryDueDate = filled($submitted['dates']['co_applicant_promissory_note_due_date'])
            ? CarbonImmutable::parse($submitted['dates']['co_applicant_promissory_note_due_date'], $timezone)->startOfDay()
            : null;
        $declarationDate = CarbonImmutable::parse($submitted['dates']['declaration_date'], $timezone)->startOfDay();

        return [
            'company' => array_merge($company, [
                'full_identity' => $this->formatCompanyIdentity($company),
            ]),
            'dates' => [
                'request_date' => $requestDate->format('Y-m-d'),
                'request_date_formatted' => $this->dateFormatter->format($requestDate),
                'request_date_words' => $this->dateFormatter->spellOut($requestDate),
                'consultation_agreement_date' => $consultationContractDate->format('Y-m-d'),
                'consultation_agreement_date_formatted' => $this->dateFormatter->format($consultationContractDate),
                'consultation_agreement_date_words' => $this->dateFormatter->spellOut($consultationContractDate),
                'application_request_date' => $requestDate->format('Y-m-d'),
                'application_request_date_formatted' => $this->dateFormatter->format($requestDate),
                'application_request_date_words' => $this->dateFormatter->spellOut($requestDate),
                'mediation_contract_date' => $mediationContractDate?->format('Y-m-d'),
                'mediation_contract_date_formatted' => $this->dateFormatter->format($mediationContractDate),
                'mediation_contract_date_words' => $this->dateFormatter->spellOut($mediationContractDate),
                'mediation_protocol_date' => $mediationProtocolDate->format('Y-m-d'),
                'mediation_protocol_date_formatted' => $this->dateFormatter->format($mediationProtocolDate),
                'mediation_protocol_date_words' => $this->dateFormatter->spellOut($mediationProtocolDate),
                'consultation_protocol_date' => $consultationProtocolDate->format('Y-m-d'),
                'consultation_protocol_date_formatted' => $this->dateFormatter->format($consultationProtocolDate),
                'consultation_protocol_date_words' => $this->dateFormatter->spellOut($consultationProtocolDate),
                'company_promissory_note_issue_date' => $companyPromissoryIssueDate->format('Y-m-d'),
                'company_promissory_note_issue_date_formatted' => $this->dateFormatter->format($companyPromissoryIssueDate),
                'company_promissory_note_issue_date_words' => $this->dateFormatter->spellOut($companyPromissoryIssueDate),
                'company_promissory_note_due_date' => $companyPromissoryDueDate?->format('Y-m-d'),
                'company_promissory_note_due_date_formatted' => $this->dateFormatter->format($companyPromissoryDueDate),
                'company_promissory_note_due_date_words' => $this->dateFormatter->spellOut($companyPromissoryDueDate),
                'loan_agreement_date' => $loanAgreementDate->format('Y-m-d'),
                'loan_agreement_date_formatted' => $this->dateFormatter->format($loanAgreementDate),
                'loan_agreement_date_words' => $this->dateFormatter->spellOut($loanAgreementDate),
                'loan_due_date' => $loanDueDate->format('Y-m-d'),
                'loan_due_date_formatted' => $this->dateFormatter->format($loanDueDate),
                'loan_due_date_words' => $this->dateFormatter->spellOut($loanDueDate),
                'co_applicant_promissory_note_issue_date' => $coApplicantPromissoryIssueDate->format('Y-m-d'),
                'co_applicant_promissory_note_issue_date_formatted' => $this->dateFormatter->format($coApplicantPromissoryIssueDate),
                'co_applicant_promissory_note_issue_date_words' => $this->dateFormatter->spellOut($coApplicantPromissoryIssueDate),
                'co_applicant_promissory_note_due_date' => $coApplicantPromissoryDueDate?->format('Y-m-d'),
                'co_applicant_promissory_note_due_date_formatted' => $this->dateFormatter->format($coApplicantPromissoryDueDate),
                'co_applicant_promissory_note_due_date_words' => $this->dateFormatter->spellOut($coApplicantPromissoryDueDate),
                'declaration_date' => $declarationDate->format('Y-m-d'),
                'declaration_date_formatted' => $this->dateFormatter->format($declarationDate),
                'declaration_date_words' => $this->dateFormatter->spellOut($declarationDate),
            ],
            'financial' => [
                'liabilities_total' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['liabilities_total_eur']),
                'monthly_repayment_burden' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['monthly_repayment_burden_eur']),
                'monthly_net_income' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['monthly_net_income_eur']),
                'fee' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['fee_eur']),
                'company_promissory_note_amount' => $this->currencyFormatter->describeEurWithBgnEquivalent(
                    $this->resolveCompanyPromissoryNoteAmountEur($submitted),
                ),
                'co_applicant_promissory_note_amount' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['co_applicant_promissory_note_amount_eur']),
                'loan_amount' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['loan_amount_eur']),
                'loan_return_amount' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['loan_return_amount_eur']),
                'loan_installment' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['loan_installment_eur']),
                'post_service_monthly_repayment_burden' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['post_service_monthly_repayment_burden_eur']),
            ],
            'identities' => [
                'client' => $this->formatPartyIdentity($submitted['client']),
                'co_applicant' => $this->formatPartyIdentity($submitted['co_applicant']),
                'company' => $this->formatCompanyIdentity($company),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $documentTypes
     * @param  array<string, mixed>  $submitted
     * @param  array<string, mixed>  $derived
     * @return array<int, array<string, mixed>>
     */
    private function generateDocuments(array $documentTypes, array $submitted, array $derived, string $directoryKey): array
    {
        $documents = [];

        $layout = is_string($submitted['document_layout'] ?? null) ? $submitted['document_layout'] : null;

        foreach ($this->expandDocumentSpecs($documentTypes, $layout) as $documentSpec) {
            $documentType = $documentSpec['document_type'];
            $documentKey = $documentSpec['document_key'];
            $copyNumber = $documentSpec['copy_number'];
            $label = $documentSpec['label'];
            $view = self::DOCUMENT_VIEWS[$documentType] ?? null;

            if ($view === null) {
                continue;
            }

            $contentHtml = $this->renderDocumentContentHtml($view, $submitted, $derived, $documentType, $copyNumber);
            $docxRelativePath = 'generated/'.$directoryKey.'/'.$documentKey.'-'.Str::uuid().'.docx';

            $this->renderDocx($contentHtml, $docxRelativePath);

            $documents[] = [
                'document_key' => $documentKey,
                'document_type' => $documentType,
                'copy_number' => $copyNumber,
                'label' => $label,
                'variants' => [
                    ContractBatch::DOCUMENT_VARIANT_DOCX => [
                        'path' => $docxRelativePath,
                        'download_name' => $this->buildDownloadFileName(
                            $label,
                            $submitted['client']['full_name'],
                            ContractBatch::DOCUMENT_VARIANT_DOCX,
                        ),
                        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'file_size' => Storage::disk('legal')->size($docxRelativePath),
                    ],
                ],
            ];
        }

        return $documents;
    }

    /**
     * @param  array<int, string>  $documentTypes
     * @return array<int, array{document_type: string, document_key: string, copy_number: int|null, label: string}>
     */
    private function expandDocumentSpecs(array $documentTypes, ?string $layout = null): array
    {
        $specs = [];

        foreach (ContractBatch::orderSelectedDocumentTypes($documentTypes, $layout) as $documentType) {
            if (in_array($documentType, [
                ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
                ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
            ], true)) {
                foreach ([1, 2] as $copyNumber) {
                    $specs[] = [
                        'document_type' => $documentType,
                        'document_key' => ContractBatch::buildGeneratedDocumentKey($documentType, $copyNumber),
                        'copy_number' => $copyNumber,
                        'label' => ContractBatch::getGeneratedDocumentLabel($documentType, $copyNumber),
                    ];
                }

                continue;
            }

            $specs[] = [
                'document_type' => $documentType,
                'document_key' => ContractBatch::buildGeneratedDocumentKey($documentType),
                'copy_number' => null,
                'label' => ContractBatch::getGeneratedDocumentLabel($documentType),
            ];
        }

        return $specs;
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @param  array{path: string, download_name: string}|null  $combinedPdf
     * @return array<string, string>|null
     */
    private function createArchive(array $documents, ?array $combinedPdf, string $directoryKey, ?string $clientFullName): ?array
    {
        if (! class_exists(ZipArchive::class) || ($documents === [] && $combinedPdf === null)) {
            return null;
        }

        $relativePath = 'generated/'.$directoryKey.'/package-'.Str::uuid().'.zip';
        $absolutePath = Storage::disk('legal')->path($relativePath);

        Storage::disk('legal')->makeDirectory(dirname($relativePath));

        $archive = new ZipArchive;

        if ($archive->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        if ($combinedPdf !== null && Storage::disk('legal')->exists($combinedPdf['path'])) {
            $archive->addFile(
                Storage::disk('legal')->path($combinedPdf['path']),
                $combinedPdf['download_name'],
            );
        }

        foreach ($documents as $document) {
            foreach (($document['variants'] ?? []) as $variant) {
                if (! is_array($variant) || ! Storage::disk('legal')->exists($variant['path'] ?? '')) {
                    continue;
                }

                $archive->addFile(
                    Storage::disk('legal')->path($variant['path']),
                    $variant['download_name'] ?? basename((string) $variant['path']),
                );
            }
        }

        $archive->close();

        return [
            'path' => $relativePath,
            'download_name' => $this->buildArchiveDownloadName($clientFullName),
        ];
    }

    /**
     * Записва предишните генерирани документи като архивна версия, вместо да ги трие.
     * Генерираните договори се пазят дори след регенериране за същия клиент.
     *
     * @param  array<int, array<string, mixed>>  $history
     * @param  array<string, mixed>|null  $previousSnapshot
     * @return array<int, array<string, mixed>>
     */
    private function appendGeneratedDocumentHistory(array $history, ?array $previousSnapshot): array
    {
        if ($previousSnapshot === null) {
            return $history;
        }

        $hasFiles = ($previousSnapshot['generated_documents'] ?? []) !== []
            || filled($previousSnapshot['combined_pdf_path'] ?? null)
            || filled($previousSnapshot['combined_docx_path'] ?? null)
            || filled($previousSnapshot['archive_path'] ?? null);

        if (! $hasFiles) {
            return $history;
        }

        $history[] = array_merge($previousSnapshot, [
            'archived_at' => CarbonImmutable::now('Europe/Sofia')->toIso8601String(),
        ]);

        return $history;
    }

    /**
     * Изтрива новогенерирани файлове при неуспешен запис (rollback). Не се използва
     * за вече запазени версии — те остават като история на договора.
     *
     * @param  array<int, array<string, mixed>>|null  $generatedDocuments
     */
    private function deleteGeneratedSnapshot(?array $generatedDocuments, ?string $combinedPdfPath, ?string $combinedDocxPath, ?string $archivePath): void
    {
        $directories = [];

        foreach ($generatedDocuments ?? [] as $document) {
            if (! is_array($document)) {
                continue;
            }

            foreach (($document['variants'] ?? []) as $variant) {
                if (! is_array($variant) || blank($variant['path'] ?? null)) {
                    continue;
                }

                $directories[] = dirname($variant['path']);
                Storage::disk('legal')->delete($variant['path']);
            }

            if (filled($document['path'] ?? null)) {
                $directories[] = dirname($document['path']);
                Storage::disk('legal')->delete($document['path']);
            }
        }

        if (filled($combinedPdfPath)) {
            $directories[] = dirname($combinedPdfPath);
            Storage::disk('legal')->delete($combinedPdfPath);
        }

        if (filled($combinedDocxPath)) {
            $directories[] = dirname($combinedDocxPath);
            Storage::disk('legal')->delete($combinedDocxPath);
        }

        if (filled($archivePath)) {
            $directories[] = dirname($archivePath);
            Storage::disk('legal')->delete($archivePath);
        }

        foreach (array_unique(array_filter($directories)) as $directory) {
            Storage::disk('legal')->deleteDirectory($directory);
        }
    }

    /**
     * @return array{path: string, download_name: string}
     */
    private function generateCombinedPdf(
        array $documentTypes,
        array $submitted,
        array $derived,
        string $directoryKey,
        ?string $clientFullName,
        ?string $layout = null,
    ): array {
        $css = <<<'CSS'
        body {
            font-family: dejavuserif, serif;
            font-size: 11pt;
            line-height: 1.45;
            color: #111827;
        }
        p { margin: 0 0 10px; text-align: justify; }
        .title { margin-bottom: 18px; font-size: 15pt; font-weight: 700; text-align: center; text-transform: uppercase; }
        .subtitle { margin-bottom: 16px; font-size: 11pt; font-weight: 700; text-align: center; }
        .section-title { margin: 16px 0 10px; font-weight: 700; text-align: center; }
        .signature-block { margin-top: 22px; }
        .signature-row { margin-top: 28px; }
        .small { font-size: 9.8pt; }
        .spacer { height: 18px; }
        .promissory-note p { margin-bottom: 6px; }
        .promissory-note .title { margin-bottom: 10px; }
        .promissory-note .signature-row { margin-top: 16px; }
CSS;

        $documentSpecs = $this->expandDocumentSpecs($documentTypes, $layout);
        $documentContents = [];
        $documentPageCounts = [];

        foreach ($documentSpecs as $spec) {
            $view = self::DOCUMENT_VIEWS[$spec['document_type']] ?? null;

            if ($view === null) {
                continue;
            }

            $contentHtml = $this->renderDocumentContentHtml(
                $view,
                $submitted,
                $derived,
                $spec['document_type'],
                $spec['copy_number'],
            );

            $documentContents[] = $contentHtml;

            $probe = $this->createCombinedPdfInstance();
            $probe->SetHTMLFooter($this->buildDocumentPageFooter(2));
            $probeHtml = '<html><head><style>'.$css.'</style></head><body>'.$contentHtml.'</body></html>';
            $probe->WriteHTML($probeHtml);
            $documentPageCounts[] = $probe->page;
        }

        $tempPdfPaths = [];

        foreach ($documentContents as $index => $contentHtml) {
            $pageCount = $documentPageCounts[$index];

            $docMpdf = $this->createCombinedPdfInstance();

            if ($pageCount > 1) {
                $docMpdf->SetHTMLFooter($this->buildDocumentPageFooter($pageCount));
            }

            $docMpdf->WriteHTML('<style>'.$css.'</style>', \Mpdf\HTMLParserMode::HEADER_CSS);
            $docMpdf->WriteHTML($contentHtml, \Mpdf\HTMLParserMode::HTML_BODY);

            $tempPath = $this->resolveTempDir().'/doc_'.$index.'_'.uniqid().'.pdf';
            $docMpdf->Output($tempPath, \Mpdf\Output\Destination::FILE);
            $tempPdfPaths[] = $tempPath;
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $this->resolveTempDir(),
        ]);

        $currentPage = 0;

        foreach ($tempPdfPaths as $index => $tempPath) {
            $docPageCount = $mpdf->setSourceFile($tempPath);

            if ($index > 0 && $currentPage % 2 === 1) {
                $mpdf->AddPage();
                $currentPage++;
            }

            for ($page = 1; $page <= $docPageCount; $page++) {
                $templateId = $mpdf->importPage($page);

                if ($currentPage > 0) {
                    $mpdf->AddPage();
                }

                $mpdf->useTemplate($templateId);
                $currentPage++;
            }
        }

        foreach ($tempPdfPaths as $tempPath) {
            @unlink($tempPath);
        }

        $relativePath = 'generated/'.$directoryKey.'/dogovori-'.Str::uuid().'.pdf';
        Storage::disk('legal')->put($relativePath, $mpdf->OutputBinaryData());

        $clientSegment = Str::slug((string) $clientFullName);
        $downloadName = trim(implode('-', array_filter([
            'dogovori',
            $clientSegment !== '' ? $clientSegment : null,
        ])), '-').'.pdf';

        return [
            'path' => $relativePath,
            'download_name' => $downloadName,
        ];
    }

    /**
     * @return array{path: string, download_name: string}
     */
    private function generateCombinedDocx(
        array $documentTypes,
        array $submitted,
        array $derived,
        string $directoryKey,
        ?string $clientFullName,
        ?string $layout = null,
    ): array {
        $documentSpecs = $this->expandDocumentSpecs($documentTypes, $layout);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $sectionBase = [
            'marginLeft' => 1020,
            'marginRight' => 1020,
            'marginTop' => 900,
            'marginBottom' => 1020,
            'pageNumberingStart' => 1,
            'breakType' => 'oddPage',
        ];

        foreach ($documentSpecs as $spec) {
            $view = self::DOCUMENT_VIEWS[$spec['document_type']] ?? null;

            if ($view === null) {
                continue;
            }

            $contentHtml = $this->renderDocumentContentHtml(
                $view,
                $submitted,
                $derived,
                $spec['document_type'],
                $spec['copy_number'],
            );

            $section = $phpWord->addSection($sectionBase);

            $footer = $section->addFooter();
            $textRun = $footer->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $fontStyle = ['size' => 9, 'color' => '6b7280', 'name' => 'Times New Roman'];
            $textRun->addText('Страница ', $fontStyle);
            $textRun->addField('PAGE', [], ['PreserveFormat']);
            $textRun->addText(' от ', $fontStyle);
            // Всеки договор е отделна секция, която рестартира номерацията (pageNumberingStart => 1).
            // PhpWord поддържа само NUMPAGES (общия брой страници на целия файл), затова добавяме
            // NUMPAGES тук и след записа го заменяме със SECTIONPAGES, за да брои страниците на
            // конкретния договор, а не общия брой на всички договори.
            $textRun->addField('NUMPAGES', [], ['PreserveFormat']);

            WordHtml::addHtml(
                $section,
                $this->prepareHtmlForWord($contentHtml),
                false,
                false,
            );
        }

        $relativePath = 'generated/'.$directoryKey.'/dogovori-'.Str::uuid().'.docx';
        Storage::disk('legal')->makeDirectory(dirname($relativePath));

        $absolutePath = Storage::disk('legal')->path($relativePath);

        WordIOFactory::createWriter($phpWord, 'Word2007')
            ->save($absolutePath);

        $this->convertNumPagesToSectionPages($absolutePath);

        $clientSegment = Str::slug((string) $clientFullName);
        $downloadName = trim(implode('-', array_filter([
            'dogovori',
            $clientSegment !== '' ? $clientSegment : null,
        ])), '-').'.docx';

        return [
            'path' => $relativePath,
            'download_name' => $downloadName,
        ];
    }

    /**
     * Замества полето NUMPAGES (общ брой страници на целия документ) със SECTIONPAGES
     * в долните колонтитули на записания .docx файл. Така всеки договор показва броя
     * на собствените си страници, вместо общия брой на всички договори в общия файл.
     */
    private function convertNumPagesToSectionPages(string $absolutePath): void
    {
        if (! class_exists(ZipArchive::class)) {
            return;
        }

        $archive = new ZipArchive;

        if ($archive->open($absolutePath) !== true) {
            return;
        }

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);

            if ($name === false || ! preg_match('#^word/footer\d+\.xml$#', $name)) {
                continue;
            }

            $contents = $archive->getFromIndex($index);

            if (! is_string($contents) || ! str_contains($contents, 'NUMPAGES')) {
                continue;
            }

            $archive->addFromString($name, str_replace('NUMPAGES', 'SECTIONPAGES', $contents));
        }

        $archive->close();
    }

    private function renderDocx(string $contentHtml, string $relativePath): void
    {
        Storage::disk('legal')->makeDirectory(dirname($relativePath));

        $phpWord = new PhpWord;
        $section = $phpWord->addSection([
            'marginLeft' => 1020,
            'marginRight' => 1020,
            'marginTop' => 900,
            'marginBottom' => 1020,
        ]);

        WordHtml::addHtml(
            $section,
            $this->prepareHtmlForWord($contentHtml),
            false,
            false,
        );

        WordIOFactory::createWriter($phpWord, 'Word2007')
            ->save(Storage::disk('legal')->path($relativePath));
    }

    private function renderPdfHtml(
        string $view,
        array $submitted,
        array $derived,
        string $documentType,
        ?int $copyNumber,
    ): string {
        return view('contracts.pdf.layout', [
            'contentView' => $view,
            'submitted' => $submitted,
            'derived' => $derived,
            'documentType' => $documentType,
            'documentCopyNumber' => $copyNumber,
        ])->render();
    }

    private function renderDocumentContentHtml(
        string $view,
        array $submitted,
        array $derived,
        string $documentType,
        ?int $copyNumber,
    ): string {
        return view($view, [
            'submitted' => $submitted,
            'derived' => $derived,
            'documentType' => $documentType,
            'documentCopyNumber' => $copyNumber,
        ])->render();
    }

    private function prepareHtmlForWord(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><!DOCTYPE html><html><body>'.$html.'</body></html>',
            LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED,
        );

        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return $html;
        }

        $this->appendInlineStyle(
            $body,
            'font-family: Times New Roman, serif; font-size: 11pt; line-height: 1.45; color: #111827;',
        );

        foreach ($body->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            $this->applyWordElementStyle($element);
        }

        return $this->getElementInnerHtml($body);
    }

    private function applyWordElementStyle(DOMElement $element): void
    {
        if ($element->tagName === 'p') {
            $this->appendInlineStyle($element, 'margin-bottom: 10px; text-align: justify;');
        }

        $classes = preg_split('/\s+/', trim((string) $element->getAttribute('class'))) ?: [];

        foreach ($classes as $class) {
            if (! isset(self::WORD_CLASS_STYLES[$class])) {
                continue;
            }

            $this->appendInlineStyle($element, self::WORD_CLASS_STYLES[$class]);
        }

        if (in_array('spacer', $classes, true) && $element->childNodes->length === 0) {
            $element->nodeValue = ' ';
        }

        if ($element->hasAttribute('class')) {
            $element->removeAttribute('class');
        }
    }

    private function appendInlineStyle(DOMElement $element, string $style): void
    {
        $existingStyle = trim((string) $element->getAttribute('style'));
        $element->setAttribute('style', trim($existingStyle.' '.$style));
    }

    private function getElementInnerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $childNode) {
            $html .= $element->ownerDocument?->saveHTML($childNode) ?? '';
        }

        return $html;
    }

    private function createCombinedPdfInstance(): Mpdf
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavuserif',
            'tempDir' => $this->resolveTempDir(),
            'margin_left' => 18,
            'margin_right' => 18,
            'margin_top' => 16,
            'margin_bottom' => 22,
        ]);

        $mpdf->SetCompression(false);

        return $mpdf;
    }

    private function buildDocumentPageFooter(int $totalPages): string
    {
        return '<div style="text-align: center; font-family: dejavuserif, serif; font-size: 9pt; color: #6b7280;">Страница {PAGENO} от '.$totalPages.'</div>';
    }

    private function resolveTempDir(): string
    {
        $tempDir = storage_path('app/private/mpdf-temp');

        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            throw new RuntimeException('Неуспешно създаване на временна директория за PDF генериране.');
        }

        return $tempDir;
    }

    /**
     * @param  array<string, string|null>  $party
     */
    private function formatPartyIdentity(array $party): string
    {
        return trim(implode(', ', array_filter([
            $party['full_name'] ?? null,
            filled($party['egn'] ?? null) ? 'ЕГН '.$party['egn'] : null,
            filled($party['id_card_number'] ?? null) ? 'притежаващ/а лична карта с номер '.$party['id_card_number'] : null,
            filled($party['id_card_issued_at'] ?? null) ? 'издадена на '.$this->dateFormatter->format($party['id_card_issued_at']).' г.' : null,
            filled($party['id_card_issued_by'] ?? null) ? 'от '.$party['id_card_issued_by'] : null,
            filled($party['permanent_address'] ?? null) ? 'с постоянен адрес: '.$party['permanent_address'] : null,
        ])));
    }

    /**
     * @param  array<string, string>  $company
     */
    private function formatCompanyIdentity(array $company): string
    {
        return trim(implode(', ', array_filter([
            $company['name'] ?? null,
            filled($company['eik'] ?? null) ? 'ЕИК '.$company['eik'] : null,
            filled($company['address'] ?? null) ? 'със седалище и адрес на управление: '.$company['address'] : null,
            (filled($company['representative_title'] ?? null) && filled($company['representative_name'] ?? null))
                ? 'представлявано от '.$company['representative_title'].' '.$company['representative_name']
                : null,
        ])));
    }

    /**
     * @param  array<int, string>  $documentTypes
     */
    private function requiresStrictCoApplicant(array $documentTypes): bool
    {
        return in_array(ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT, $documentTypes, true)
            || in_array(ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE, $documentTypes, true)
            || in_array(ContractBatch::DOCUMENT_TYPE_DECLARATION, $documentTypes, true)
            || in_array(ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION, $documentTypes, true);
    }

    /**
     * @param  array<int, string>  $documentTypes
     */
    private function requiresMediationContractDate(array $documentTypes): bool
    {
        return in_array(ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT, $documentTypes, true)
            || in_array(ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL, $documentTypes, true);
    }

    /**
     * @param  array<int, string>  $documentTypes
     */
    private function requiresApplicationRequest(array $documentTypes): bool
    {
        return in_array(ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST, $documentTypes, true);
    }

    /**
     * @param  array<int, string>  $documentTypes
     */
    private function requiresProtocolOutcomeData(array $documentTypes): bool
    {
        return in_array(ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL, $documentTypes, true)
            || in_array(ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL, $documentTypes, true);
    }

    /**
     * @param  array<int, string>  $documentTypes
     */
    private function requiresFee(array $documentTypes): bool
    {
        return count(array_intersect($documentTypes, self::FEE_DOCUMENT_TYPES)) > 0;
    }

    /**
     * @param  array<int, string>  $documentTypes
     */
    private function requiresLoanFields(array $documentTypes): bool
    {
        return in_array(ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT, $documentTypes, true);
    }

    /**
     * @param  array<string, mixed>  $submitted
     */
    private function isBridgeCreditLayout(array $submitted): bool
    {
        return ($submitted['document_layout'] ?? null) === ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT;
    }

    /**
     * При мостов кредит сумата по записа на заповед към фирмата се въвежда ръчно,
     * защото се различава от комисионната в консултантския договор. При всички
     * останали видове записът продължава да се генерира от възнаграждението.
     *
     * @param  array<string, mixed>  $submitted
     */
    private function resolveCompanyPromissoryNoteAmountEur(array $submitted): ?float
    {
        if ($this->isBridgeCreditLayout($submitted)) {
            return $submitted['financial']['company_promissory_note_amount_eur'];
        }

        return $submitted['financial']['fee_eur'];
    }

    private function buildDownloadFileName(
        string $label,
        ?string $clientFullName,
        string $format = ContractBatch::DOCUMENT_VARIANT_PDF,
    ): string {
        $baseName = Str::slug($label);
        $clientSegment = Str::slug((string) $clientFullName);
        $extension = $format === ContractBatch::DOCUMENT_VARIANT_DOCX
            ? 'docx'
            : 'pdf';

        return trim(implode('-', array_filter([
            $baseName !== '' ? $baseName : 'document',
            $clientSegment !== '' ? $clientSegment : null,
        ])), '-').'.'.$extension;
    }

    private function buildArchiveDownloadName(?string $clientFullName): string
    {
        $clientSegment = Str::slug((string) $clientFullName);

        return trim(implode('-', array_filter([
            'dogovori',
            $clientSegment !== '' ? $clientSegment : null,
        ])), '-').'.zip';
    }

    private function normalizeText(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : null;

        return filled($normalized) ? $normalized : null;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeDayOfMonth(mixed $value): ?int
    {
        $integer = $this->normalizeInteger($value);

        if ($integer === null) {
            return null;
        }

        return max(1, min(31, $integer));
    }

    private function normalizeAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = is_string($value)
            ? str_replace([' ', ','], ['', '.'], trim($value))
            : (string) $value;

        return round((float) $normalized, 2);
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $value, 'Europe/Sofia')->format('Y-m-d');
    }
}
