<?php

namespace App\Filament\Resources\ContractBatches\Schemas;

use App\Models\ContractBatch;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Services\Contracts\ContractGenerationService;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ContractBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('lead_id'),
                Section::make('Фирма и документи')
                    ->columns(2)
                    ->schema([
                        Select::make('company_key')
                            ->label('Фирма')
                            ->options(ContractBatch::getCompanyOptions())
                            ->required()
                            ->native(false),
                        DatePicker::make('dates.request_date')
                            ->label('Дата на заявка')
                            ->required()
                            ->native(false),
                        DatePicker::make('dates.mediation_contract_date')
                            ->label('Дата на договор за посредничество')
                            ->required(fn (Get $get): bool => static::hasSelected($get, [
                                ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
                                ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                            ]))
                            ->visible(fn (Get $get): bool => static::hasSelected($get, [
                                ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
                                ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                            ]))
                            ->native(false),
                        CheckboxList::make('selected_document_types')
                            ->label('Документи за генериране')
                            ->options(ContractBatch::getDocumentTypeOptions())
                            ->required()
                            ->columns(2)
                            ->default(array_keys(ContractBatch::getDocumentTypeOptions()))
                            ->live()
                            ->columnSpanFull(),
                    ]),
                Section::make('Източник на данни')
                    ->description('Данните се зареждат от заявката и могат да се редактират преди генериране.')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('lead_id')))
                    ->schema([
                        Placeholder::make('lead_source')
                            ->label('Заявка')
                            ->content(fn (Get $get): string => static::getLeadSourceLabel($get)),
                        Select::make('lead_guarantor_id')
                            ->label('Гарант / съкредитоискател от заявката')
                            ->options(fn (Get $get): array => static::getLeadGuarantorOptions($get))
                            ->visible(fn (Get $get): bool => static::getLeadGuarantorOptions($get) !== [])
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                if (! is_numeric($state) || ! is_numeric($get('lead_id'))) {
                                    return;
                                }

                                $guarantor = LeadGuarantor::query()
                                    ->where('lead_id', (int) $get('lead_id'))
                                    ->find((int) $state);

                                static::fillCoApplicantFromGuarantor($set, $guarantor);
                            })
                            ->native(false),
                    ]),
                Section::make('Автоматични дати')
                    ->description('Полетата се попълват автоматично, но могат да се редактират ръчно преди генериране.')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('dates.mediation_protocol_date')
                            ->label('Дата на протокол по посредничество')
                            ->default(static::today())
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL))
                            ->native(false),
                        DatePicker::make('dates.consultation_protocol_date')
                            ->label('Дата на протокол за извършена консултация')
                            ->default(static::twoWorkingDaysAgo())
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL))
                            ->native(false),
                        DatePicker::make('dates.company_promissory_note_issue_date')
                            ->label('Дата на издаване на записа на заповед към фирмата')
                            ->default(static::today())
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE))
                            ->native(false),
                        DatePicker::make('dates.company_promissory_note_due_date')
                            ->label('Падеж на записа на заповед към фирмата')
                            ->required(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE))
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE))
                            ->native(false),
                        DatePicker::make('dates.loan_agreement_date')
                            ->label('Дата на договор за паричен заем')
                            ->default(static::today())
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT))
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if (blank($state)) {
                                    return;
                                }

                                $set('dates.loan_due_date', CarbonImmutable::parse($state, 'Europe/Sofia')->addYears(2)->format('Y-m-d'));
                            })
                            ->native(false),
                        DatePicker::make('dates.loan_due_date')
                            ->label('Крайна дата по договора за заем')
                            ->default(static::twoYearsAfterToday())
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT))
                            ->native(false),
                        DatePicker::make('dates.co_applicant_promissory_note_issue_date')
                            ->label('Дата на издаване на записа на заповед между клиента и съкредитоискателя')
                            ->default(static::today())
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE))
                            ->native(false),
                        DatePicker::make('dates.co_applicant_promissory_note_due_date')
                            ->label('Падеж на записа на заповед между клиента и съкредитоискателя')
                            ->required(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE))
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE))
                            ->native(false),
                        DatePicker::make('dates.declaration_date')
                            ->label('Дата на декларацията')
                            ->default(static::today())
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_DECLARATION))
                            ->native(false),
                    ]),
                Section::make('Клиент')
                    ->columns(2)
                    ->schema([
                        TextInput::make('client.full_name')
                            ->label('Три имена')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('client.egn')
                            ->label('ЕГН')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('client.id_card_number')
                            ->label('Лична карта №')
                            ->required()
                            ->maxLength(32),
                        DatePicker::make('client.id_card_issued_at')
                            ->label('Издадена на')
                            ->required()
                            ->native(false),
                        TextInput::make('client.id_card_issued_by')
                            ->label('Издадена от')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('client.permanent_address')
                            ->label('Постоянен адрес')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('client.email')
                            ->label('Имейл')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Съкредитоискател')
                    ->description('Попълва се, когато документът изисква второ лице или когато съкредитоискателят участва като поръчител/авалист.')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => static::requiresCoApplicantSection($get))
                    ->schema([
                        TextInput::make('co_applicant.full_name')
                            ->label('Три имена')
                            ->required(fn (Get $get): bool => static::requiresStrictCoApplicant($get))
                            ->maxLength(255),
                        TextInput::make('co_applicant.egn')
                            ->label('ЕГН')
                            ->required(fn (Get $get): bool => static::requiresStrictCoApplicant($get))
                            ->maxLength(10),
                        TextInput::make('co_applicant.id_card_number')
                            ->label('Лична карта №')
                            ->required(fn (Get $get): bool => static::requiresStrictCoApplicant($get))
                            ->maxLength(32),
                        DatePicker::make('co_applicant.id_card_issued_at')
                            ->label('Издадена на')
                            ->required(fn (Get $get): bool => static::requiresStrictCoApplicant($get))
                            ->native(false),
                        TextInput::make('co_applicant.id_card_issued_by')
                            ->label('Издадена от')
                            ->required(fn (Get $get): bool => static::requiresStrictCoApplicant($get))
                            ->maxLength(255),
                        Textarea::make('co_applicant.permanent_address')
                            ->label('Постоянен адрес')
                            ->required(fn (Get $get): bool => static::requiresStrictCoApplicant($get))
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('co_applicant.email')
                            ->label('Имейл')
                            ->email()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Финансови данни преди услугата')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => static::requiresBeforeServiceFinancialData($get))
                    ->schema([
                        TextInput::make('financial.active_credit_count')
                            ->label('Брой активни кредити')
                            ->required(fn (Get $get): bool => static::requiresBeforeServiceFinancialData($get))
                            ->numeric()
                            ->minValue(0),
                        static::euroAmountField('financial.liabilities_total_eur', 'Общ размер на задълженията')
                            ->required(fn (Get $get): bool => static::requiresApplicationRequest($get))
                            ->visible(fn (Get $get): bool => static::requiresApplicationRequest($get)),
                        static::euroAmountField('financial.monthly_repayment_burden_eur', 'Обща месечна погасителна тежест')
                            ->required(fn (Get $get): bool => static::requiresBeforeServiceFinancialData($get)),
                        static::euroAmountField('financial.monthly_net_income_eur', 'Среден месечен нетен доход')
                            ->required(fn (Get $get): bool => static::requiresApplicationRequest($get))
                            ->visible(fn (Get $get): bool => static::requiresApplicationRequest($get)),
                    ]),
                Section::make('Резултат след услугата')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => static::hasSelected($get, [
                        ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
                        ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                    ]))
                    ->schema([
                        TextInput::make('financial.post_service_credit_count')
                            ->label('Брой кредити след услугата')
                            ->required(fn (Get $get): bool => static::hasSelected($get, [
                                ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
                                ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                            ]))
                            ->numeric()
                            ->minValue(0),
                        static::euroAmountField('financial.post_service_monthly_repayment_burden_eur', 'Месечна вноска след услугата')
                            ->required(fn (Get $get): bool => static::hasSelected($get, [
                                ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
                                ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                            ])),
                    ]),
                Section::make('Възнаграждение')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => static::requiresFee($get))
                    ->schema([
                        static::euroAmountField('financial.fee_eur', 'Възнаграждение')
                            ->required(fn (Get $get): bool => static::requiresFee($get))
                            ->columnSpanFull(),
                    ]),
                Section::make('Запис на заповед между клиент и съкредитоискател')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE))
                    ->schema([
                        static::euroAmountField('financial.co_applicant_promissory_note_amount_eur', 'Сума на запис на заповед')
                            ->required(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE)),
                    ]),
                Section::make('Данни за кредит и заем')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => static::requiresLoanOrCreditDetails($get))
                    ->schema([
                        TextInput::make('loan.credit_agreement_number')
                            ->label('Номер на договор за кредит')
                            ->required(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL))
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL))
                            ->maxLength(255),
                        TextInput::make('loan.creditor_name')
                            ->label('Кредитор')
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL))
                            ->maxLength(255),
                        TextInput::make('loan.institution_name')
                            ->label('Финансова институция или банка')
                            ->required(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT))
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT))
                            ->columnSpanFull()
                            ->maxLength(255),
                        static::euroAmountField('financial.loan_amount_eur', 'Размер на кредита')
                            ->required(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT))
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT)),
                        static::euroAmountField('financial.loan_return_amount_eur', 'Сума за връщане')
                            ->required(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT))
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT)),
                        static::euroAmountField('financial.loan_installment_eur', 'Месечна вноска')
                            ->required(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT))
                            ->visible(fn (Get $get): bool => static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT)),
                    ]),
            ]);
    }

    private static function euroAmountField(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->inputMode('decimal')
            ->minValue(0)
            ->step('0.01')
            ->prefix('EUR');
    }

    private static function requiresCoApplicantSection(Get $get): bool
    {
        return count(array_intersect(static::selectedTypes($get), ContractBatch::getCoApplicantDocumentTypes())) > 0;
    }

    private static function requiresStrictCoApplicant(Get $get): bool
    {
        return static::hasSelected($get, [
            ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
            ContractBatch::DOCUMENT_TYPE_DECLARATION,
        ]);
    }

    private static function requiresBeforeServiceFinancialData(Get $get): bool
    {
        return static::hasSelected($get, [
            ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
        ]);
    }

    private static function requiresApplicationRequest(Get $get): bool
    {
        return static::hasSelected($get, ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST);
    }

    private static function requiresFee(Get $get): bool
    {
        return static::hasSelected($get, [
            ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
        ]);
    }

    private static function requiresLoanOrCreditDetails(Get $get): bool
    {
        return static::hasSelected($get, [
            ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_LOAN_AGREEMENT,
        ]);
    }

    /**
     * @param  string|array<int, string>  $types
     */
    private static function hasSelected(Get $get, string|array $types): bool
    {
        $selected = static::selectedTypes($get);

        return count(array_intersect($selected, (array) $types)) > 0;
    }

    /**
     * @return array<int, string>
     */
    private static function selectedTypes(Get $get): array
    {
        return array_values(array_filter(
            $get('selected_document_types') ?? [],
            static fn (mixed $value): bool => is_string($value) && filled($value),
        ));
    }

    private static function today(): string
    {
        return CarbonImmutable::now('Europe/Sofia')->format('Y-m-d');
    }

    private static function twoYearsAfterToday(): string
    {
        return CarbonImmutable::now('Europe/Sofia')->addYears(2)->format('Y-m-d');
    }

    private static function twoWorkingDaysAgo(): string
    {
        $date = CarbonImmutable::now('Europe/Sofia')->startOfDay();
        $remainingDays = 2;

        while ($remainingDays > 0) {
            $date = $date->subDay();

            if ($date->isWeekend()) {
                continue;
            }

            $remainingDays--;
        }

        return $date->format('Y-m-d');
    }

    private static function getLeadSourceLabel(Get $get): string
    {
        $leadId = $get('lead_id');

        if (! is_numeric($leadId)) {
            return 'Няма';
        }

        $lead = Lead::query()->find((int) $leadId);

        if (! $lead instanceof Lead) {
            return 'Няма';
        }

        $fullName = trim(implode(' ', array_filter([
            $lead->first_name,
            $lead->middle_name,
            $lead->last_name,
        ])));

        return trim(implode(' - ', array_filter([
            '#'.$lead->id,
            filled($fullName) ? $fullName : null,
        ])));
    }

    /**
     * @return array<int, string>
     */
    private static function getLeadGuarantorOptions(Get $get): array
    {
        $leadId = $get('lead_id');

        if (! is_numeric($leadId)) {
            return [];
        }

        return LeadGuarantor::query()
            ->where('lead_id', (int) $leadId)
            ->orderBy('id')
            ->get()
            ->mapWithKeys(static function (LeadGuarantor $guarantor): array {
                $fullName = trim(implode(' ', array_filter([
                    $guarantor->first_name,
                    $guarantor->middle_name,
                    $guarantor->last_name,
                ])));

                return [
                    $guarantor->id => filled($fullName)
                        ? $fullName
                        : 'Гарант #'.$guarantor->id,
                ];
            })
            ->all();
    }

    private static function fillCoApplicantFromGuarantor(Set $set, ?LeadGuarantor $guarantor): void
    {
        $prefill = app(ContractGenerationService::class)
            ->buildCoApplicantPrefillFromGuarantor($guarantor);

        foreach ($prefill as $field => $value) {
            if ($value === null) {
                continue;
            }

            $set('co_applicant.'.$field, $value);
        }
    }
}
