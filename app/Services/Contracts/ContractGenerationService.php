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
        ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL => 'contracts.pdf.documents.consultation_protocol',
        ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE => 'contracts.pdf.documents.company_promissory_note',
        ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT => 'contracts.pdf.documents.loan_agreement',
        ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE => 'contracts.pdf.documents.co_applicant_promissory_note',
        ContractBatch::DOCUMENT_TYPE_DECLARATION => 'contracts.pdf.documents.declaration',
    ];

    /**
     * @var array<int, string>
     */
    private const FEE_DOCUMENT_TYPES = [
        ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
        ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
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
     * @return array<string, mixed>
     */
    public function buildFormPrefillFromLead(Lead $lead, ?LeadGuarantor $guarantor = null): array
    {
        $selectedGuarantor = $this->resolveLeadGuarantor($lead, $guarantor);
        $requestDate = $this->buildRequestDateFromLead($lead)
            ?? CarbonImmutable::now('Europe/Sofia')->format('Y-m-d');

        return [
            'lead_id' => $lead->id,
            'lead_guarantor_id' => $selectedGuarantor?->id,
            'client' => $this->buildPartyPrefillFromLead($lead),
            'co_applicant' => $this->buildPartyPrefillFromGuarantor($selectedGuarantor),
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
    }

    /**
     * @return array<string, string|null>
     */
    public function buildCoApplicantPrefillFromGuarantor(?LeadGuarantor $guarantor): array
    {
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

    private function persistBatch(?ContractBatch $existingBatch, array $input, User $actor): ContractBatch
    {
        $submitted = $this->normalizeInput($input);
        $derived = $this->buildDerivedData($submitted);
        $directoryKey = (string) Str::uuid();

        $generatedDocuments = [];
        $archive = null;
        $previousGeneratedDocuments = $existingBatch?->generated_documents;
        $previousArchivePath = $existingBatch?->archive_path;

        try {
            $generatedDocuments = $this->generateDocuments(
                $submitted['selected_document_types'],
                $submitted,
                $derived,
                $directoryKey,
            );

            $archive = $this->createArchive($generatedDocuments, $directoryKey, $submitted['client']['full_name']);

            $batch = DB::transaction(function () use (
                $existingBatch,
                $submitted,
                $derived,
                $generatedDocuments,
                $archive,
                $actor
            ): ContractBatch {
                $batch = $existingBatch ?? new ContractBatch;

                $batch->fill([
                    'lead_id' => $submitted['lead_id'],
                    'company_key' => $submitted['company_key'],
                    'client_full_name' => $submitted['client']['full_name'],
                    'co_applicant_full_name' => $submitted['co_applicant']['full_name'] ?: null,
                    'request_date' => $submitted['dates']['request_date'],
                    'selected_document_types' => $submitted['selected_document_types'],
                    'input_payload' => [
                        'submitted' => $submitted,
                        'derived' => $derived,
                    ],
                    'generated_documents' => $generatedDocuments,
                    'archive_path' => $archive['path'] ?? null,
                    'archive_file_name' => $archive['download_name'] ?? null,
                    'generated_at' => now(),
                ]);

                if (! $batch->exists) {
                    $batch->created_by_user_id = $actor->id;
                }

                $batch->save();

                return $batch->fresh();
            });

            $this->deleteGeneratedSnapshot($previousGeneratedDocuments, $previousArchivePath);

            return $batch;
        } catch (Throwable $throwable) {
            $this->deleteGeneratedSnapshot($generatedDocuments, $archive['path'] ?? null);

            throw $throwable;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        $companyKey = $this->normalizeText($input['company_key'] ?? null);
        $selectedDocumentTypes = ContractBatch::orderSelectedDocumentTypes($input['selected_document_types'] ?? []);
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

        $submitted = [
            'lead_id' => $sourceLead?->id,
            'lead_guarantor_id' => $sourceGuarantor?->id,
            'company_key' => $companyKey,
            'client' => $client,
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
                'co_applicant_promissory_note_amount_eur' => $this->normalizeAmount(data_get($input, 'financial.co_applicant_promissory_note_amount_eur')),
                'loan_amount_eur' => $this->normalizeAmount(data_get($input, 'financial.loan_amount_eur'))
                    ?? $this->normalizeAmount($sourceLead?->amount),
                'loan_return_amount_eur' => $this->normalizeAmount(data_get($input, 'financial.loan_return_amount_eur')),
                'loan_installment_eur' => $this->normalizeAmount(data_get($input, 'financial.loan_installment_eur')),
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
                'consultation_protocol_date' => $this->normalizeDateString(data_get($input, 'dates.consultation_protocol_date')),
                'company_promissory_note_issue_date' => $this->normalizeDateString(data_get($input, 'dates.company_promissory_note_issue_date')),
                'company_promissory_note_due_date' => $this->normalizeDateString(data_get($input, 'dates.company_promissory_note_due_date')),
                'loan_agreement_date' => $this->normalizeDateString(data_get($input, 'dates.loan_agreement_date')),
                'loan_due_date' => $this->normalizeDateString(data_get($input, 'dates.loan_due_date')),
                'co_applicant_promissory_note_issue_date' => $this->normalizeDateString(data_get($input, 'dates.co_applicant_promissory_note_issue_date')),
                'co_applicant_promissory_note_due_date' => $this->normalizeDateString(data_get($input, 'dates.co_applicant_promissory_note_due_date')),
                'declaration_date' => $this->normalizeDateString(data_get($input, 'dates.declaration_date')),
            ]),
            'selected_document_types' => $selectedDocumentTypes,
        ];

        $this->validateSubmittedInput($submitted);

        return $submitted;
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

        return [
            'request_date' => $dates['request_date'] ?? null,
            'mediation_contract_date' => $dates['mediation_contract_date'] ?? null,
            'mediation_protocol_date' => filled($dates['mediation_protocol_date'] ?? null)
                ? CarbonImmutable::parse($dates['mediation_protocol_date'], $timezone)->format('Y-m-d')
                : $today->format('Y-m-d'),
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

        if (in_array(ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE, $submitted['selected_document_types'], true)
            && $submitted['dates']['company_promissory_note_due_date'] === null) {
            throw new RuntimeException('Попълнете падеж на записа на заповед към фирмата.');
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
                'consultation_agreement_date' => $requestDate->format('Y-m-d'),
                'consultation_agreement_date_formatted' => $this->dateFormatter->format($requestDate),
                'consultation_agreement_date_words' => $this->dateFormatter->spellOut($requestDate),
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
                'company_promissory_note_amount' => $this->currencyFormatter->describeEurWithBgnEquivalent($submitted['financial']['fee_eur']),
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

        foreach ($this->expandDocumentSpecs($documentTypes) as $documentSpec) {
            $documentType = $documentSpec['document_type'];
            $documentKey = $documentSpec['document_key'];
            $copyNumber = $documentSpec['copy_number'];
            $label = $documentSpec['label'];
            $view = self::DOCUMENT_VIEWS[$documentType] ?? null;

            if ($view === null) {
                continue;
            }

            $contentHtml = $this->renderDocumentContentHtml($view, $submitted, $derived, $documentType, $copyNumber);
            $pdfHtml = $this->renderPdfHtml($view, $submitted, $derived, $documentType, $copyNumber);
            $pdfRelativePath = 'generated/'.$directoryKey.'/'.$documentKey.'-'.Str::uuid().'.pdf';
            $docxRelativePath = 'generated/'.$directoryKey.'/'.$documentKey.'-'.Str::uuid().'.docx';

            Storage::disk('legal')->put($pdfRelativePath, $this->renderPdf($pdfHtml));
            $this->renderDocx($contentHtml, $docxRelativePath);

            $documents[] = [
                'document_key' => $documentKey,
                'document_type' => $documentType,
                'copy_number' => $copyNumber,
                'label' => $label,
                'variants' => [
                    ContractBatch::DOCUMENT_VARIANT_PDF => [
                        'path' => $pdfRelativePath,
                        'download_name' => $this->buildDownloadFileName(
                            $label,
                            $submitted['client']['full_name'],
                            ContractBatch::DOCUMENT_VARIANT_PDF,
                        ),
                        'mime_type' => 'application/pdf',
                        'file_size' => Storage::disk('legal')->size($pdfRelativePath),
                    ],
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
    private function expandDocumentSpecs(array $documentTypes): array
    {
        $specs = [];

        foreach (ContractBatch::orderSelectedDocumentTypes($documentTypes) as $documentType) {
            if ($documentType === ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT) {
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
     * @return array<string, string>|null
     */
    private function createArchive(array $documents, string $directoryKey, ?string $clientFullName): ?array
    {
        if (! class_exists(ZipArchive::class) || $documents === []) {
            return null;
        }

        $relativePath = 'generated/'.$directoryKey.'/package-'.Str::uuid().'.zip';
        $absolutePath = Storage::disk('legal')->path($relativePath);

        Storage::disk('legal')->makeDirectory(dirname($relativePath));

        $archive = new ZipArchive;

        if ($archive->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
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
     * @param  array<int, array<string, mixed>>|null  $generatedDocuments
     */
    private function deleteGeneratedSnapshot(?array $generatedDocuments, ?string $archivePath): void
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

        if (filled($archivePath)) {
            $directories[] = dirname($archivePath);
            Storage::disk('legal')->delete($archivePath);
        }

        foreach (array_unique(array_filter($directories)) as $directory) {
            Storage::disk('legal')->deleteDirectory($directory);
        }
    }

    private function renderPdf(string $html): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'tempDir' => $this->resolveTempDir(),
            'margin_left' => 18,
            'margin_right' => 18,
            'margin_top' => 16,
            'margin_bottom' => 18,
        ]);

        $mpdf->SetCompression(false);
        $mpdf->WriteHTML($html);

        return $mpdf->OutputBinaryData();
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
            'font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.45; color: #111827;',
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
            || in_array(ContractBatch::DOCUMENT_TYPE_DECLARATION, $documentTypes, true);
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
