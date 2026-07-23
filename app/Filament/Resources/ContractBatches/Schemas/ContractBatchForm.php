<?php

namespace App\Filament\Resources\ContractBatches\Schemas;

use App\Models\ContractBatch;
use App\Services\Contracts\ContractGenerationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ContractBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return static::configureStepOne($schema);
    }

    public static function configureStepOne(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('lead_id'),
                Hidden::make('dates.request_date')->dehydrated(),
                Hidden::make('company_key')->default(ContractBatch::COMPANY_REKREDO_KONSULT_DPK)->dehydrated(),

                Grid::make(2)
                    ->extraAttributes(['class' => 'cz-contract-toprow'])
                    ->schema([
                        Select::make('document_layout')
                            ->label('Вид Документи')
                            ->options(ContractBatch::getLayoutOptions())
                            ->default(ContractBatch::DOCUMENT_LAYOUT_FULL)
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('client.city')
                            ->label('Град')
                            ->prefix('гр.')
                            ->required()
                            ->maxLength(120),
                    ]),

                Grid::make(2)
                    ->extraAttributes(['class' => 'cz-contract-flatrow'])
                    ->schema([
                        Section::make('Данни на Клиент')
                            ->extraAttributes(['class' => 'cz-contract-flat-section'])
                            ->columns(2)
                            ->columnSpan(fn (Get $get): int => static::isLayout($get, ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR) ? 2 : 1)
                            ->schema(static::partyFields('client', strictRequired: true)),
                        Section::make('Данни на Авалист')
                            ->extraAttributes(['class' => 'cz-contract-flat-section'])
                            ->columns(2)
                            ->visible(fn (Get $get): bool => ! static::isLayout($get, ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR))
                            ->schema([
                                static::guarantorSelectField(),
                                ...static::partyFields('co_applicant', strictRequired: true),
                            ]),
                    ]),

                Section::make('Данни за Кредити')
                    ->extraAttributes(['class' => 'cz-contract-flat-section'])
                    ->columns(5)
                    ->visible(fn (Get $get): bool => static::isLayout($get, [
                        ContractBatch::DOCUMENT_LAYOUT_FULL,
                        ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
                        ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR,
                        ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
                        ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT,
                    ]))
                    ->schema([
                        static::countField('financial.credit_count_in_institutions', 'В Финансови Институции', 'Пример: 3'),
                        static::countField('financial.institution_count', 'Брой Институции', 'Пример: 2'),
                        static::countField('financial.credit_count_in_banks', 'Кредити в Банки', 'Пример: 2'),
                        static::countField('financial.bank_count', 'Брой Банки', 'Пример: 2'),
                        static::euroAmountField('financial.total_loan_amount_eur', 'Общ Размер', 'Пример: 30000')
                            ->required(fn (Get $get): bool => static::isLayout($get, [
                                ContractBatch::DOCUMENT_LAYOUT_FULL,
                                ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
                                ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR,
                                ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
                                ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT,
                            ])),
                        static::euroAmountField('financial.commission_eur', 'Комисионна', 'Пример: 2500')
                            ->required(fn (Get $get): bool => static::isLayout($get, [
                                ContractBatch::DOCUMENT_LAYOUT_FULL,
                                ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
                                ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR,
                                ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
                                ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT,
                            ])),
                        static::euroAmountField('financial.monthly_payments_eur', 'Месечни Вноски', 'Пример: 1000'),
                        static::euroAmountField('financial.private_loans_eur', 'Частни Заеми', 'Пример: 5000'),
                        static::euroAmountField('financial.net_income_eur', 'Доход (Нетно)', 'Пример: 3000')
                            ->required(fn (Get $get): bool => static::isLayout($get, ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M)),
                        static::euroAmountField('financial.court_required_eur', 'Съдебно Изискуеми', 'Пример: 3000'),
                        static::countField('financial.post_service_credit_count', 'Кредити след съдействие', 'Пример: 1'),
                        static::euroAmountField('financial.post_service_monthly_repayment_burden_eur', 'Вноска след съдействие', 'Пример: 500'),
                    ]),
            ]);
    }

    public static function configureStepTwo(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('lead_id'),
                Hidden::make('lead_guarantor_id'),
                Hidden::make('dates.request_date')->dehydrated(),
                Hidden::make('document_layout')->dehydrated(),
                Hidden::make('client.city')->dehydrated(),

                ...static::stepOneHiddenMirror(),

                // Full / Мостов кредит: 3 boxed cards in ONE row (consultation 4 cols, promissory 2 cols, loan 6 cols)
                Grid::make(12)
                    ->visible(fn (Get $get): bool => static::isLayout($get, [
                        ContractBatch::DOCUMENT_LAYOUT_FULL,
                        ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT,
                    ]))
                    ->schema([
                        static::consultationSection()->columnSpan(4),
                        static::promissorySection()->columnSpan(2),
                        static::loanSection()->columnSpan(6),
                    ]),

                // Опростен / Опростен без поръчител / Договор 12м: 2 boxed cards in ONE row
                Grid::make(2)
                    ->visible(fn (Get $get): bool => static::isLayout($get, [
                        ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
                        ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR,
                        ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
                    ]))
                    ->schema([
                        static::consultationSection(),
                        static::promissorySection(),
                    ]),

                // Loan only: just the loan card, full width
                Grid::make(1)
                    ->visible(fn (Get $get): bool => static::isLayout($get, ContractBatch::DOCUMENT_LAYOUT_LOAN_ONLY))
                    ->schema([
                        static::loanSection(),
                    ]),
            ]);
    }

    private static function consultationSection(): Section
    {
        return Section::make('Договор за Консултантска Услуга и Протокол')
            ->columns(3)
            ->schema([
                static::datePickerField('dates.consultation_contract_date', 'Дата на Договор')
                    ->required(fn (Get $get): bool => static::isLayout($get, [
                        ContractBatch::DOCUMENT_LAYOUT_FULL,
                        ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
                        ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR,
                        ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
                        ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT,
                    ]))
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if (filled($state) && blank($get('dates.request_date'))) {
                            $set('dates.request_date', $state);
                        }
                    }),
                static::datePickerField('dates.consultation_protocol_date', 'Дата на Протокол'),
                Select::make('company_key')
                    ->label('Фирма')
                    ->options(ContractBatch::getCompanyOptions())
                    ->required()
                    ->native(false),
            ]);
    }

    private static function promissorySection(): Section
    {
        return Section::make('Запис на Заповед (Клиент към нас)')
            ->columns(2)
            ->schema([
                static::datePickerField('dates.company_promissory_note_issue_date', 'Дата на Издаване'),
                static::datePickerField('dates.company_promissory_note_due_date', 'Дата на Плащане')
                    ->required(fn (Get $get): bool => static::isLayout($get, [
                        ContractBatch::DOCUMENT_LAYOUT_FULL,
                        ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
                        ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR,
                        ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
                        ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT,
                    ])),
                // При мостов кредит сумата се въвежда ръчно — различава се от комисионната в консултантския договор.
                static::euroAmountField('financial.company_promissory_note_amount_eur', 'Сума на запис на заповед', 'Пример: 5000')
                    ->visible(fn (Get $get): bool => static::isLayout($get, ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT))
                    ->required(fn (Get $get): bool => static::isLayout($get, ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT))
                    ->columnSpanFull(),
            ]);
    }

    private static function loanSection(): Section
    {
        return Section::make('Договор за Заем и Запис на Заповед (Клиент към Поръчител)')
            ->columns(4)
            ->schema([
                static::datePickerField('dates.loan_agreement_date', 'Дата на Договор')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if (filled($state) && blank($get('dates.request_date'))) {
                            $set('dates.request_date', $state);
                        }
                    }),
                static::euroAmountField('financial.loan_amount_eur', 'Размер на Заем')->required(),
                static::euroAmountField('financial.loan_return_amount_eur', 'Сума за Връщане')->required(),
                static::euroAmountField('financial.loan_installment_eur', 'Месечна Вноска')->required(),
                TextInput::make('loan.institution_name')
                    ->label('Име на Банка')
                    ->columnSpan(2)
                    ->maxLength(255),
                TextInput::make('financial.loan_installment_day_of_month')
                    ->label('Дата на месечна вноска')
                    ->suffix('число')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(31),
                static::datePickerField('dates.loan_last_installment_date', 'Дата на последна вноска'),
                static::datePickerField('dates.co_applicant_promissory_note_due_date', 'Падеж на запис на заповед')
                    ->required()
                    ->columnSpan(2),
                static::euroAmountField('financial.co_applicant_promissory_note_amount_eur', 'Сума на запис на заповед')
                    ->required()
                    ->columnSpan(2),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function stepOneHiddenMirror(): array
    {
        return [
            Hidden::make('client.full_name')->dehydrated(),
            Hidden::make('client.egn')->dehydrated(),
            Hidden::make('client.permanent_address')->dehydrated(),
            Hidden::make('client.id_card_number')->dehydrated(),
            Hidden::make('client.id_card_issued_at')->dehydrated(),
            Hidden::make('client.id_card_issued_by')->dehydrated(),
            Hidden::make('co_applicant.full_name')->dehydrated(),
            Hidden::make('co_applicant.egn')->dehydrated(),
            Hidden::make('co_applicant.permanent_address')->dehydrated(),
            Hidden::make('co_applicant.id_card_number')->dehydrated(),
            Hidden::make('co_applicant.id_card_issued_at')->dehydrated(),
            Hidden::make('co_applicant.id_card_issued_by')->dehydrated(),
        ];
    }

    /**
     * Падащ списък с поръчителите на запитването. Показва се само когато има повече
     * от един поръчител, позволява търсене и попълва данните на авалиста при избор.
     */
    private static function guarantorSelectField(): Select
    {
        return Select::make('lead_guarantor_id')
            ->label('Избери поръчител от запитването')
            ->helperText('Запитването има няколко поръчителя — изберете кой да е авалист по договора.')
            ->options(fn (Get $get): array => app(ContractGenerationService::class)->guarantorSelectOptions($get('lead_id')))
            ->searchable()
            ->native(false)
            ->live()
            ->columnSpanFull()
            ->visible(fn (Get $get): bool => app(ContractGenerationService::class)->countLeadGuarantors($get('lead_id')) >= 2)
            ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                $prefill = app(ContractGenerationService::class)
                    ->buildCoApplicantPrefillForGuarantorId($get('lead_id'), $state);

                foreach ($prefill as $field => $value) {
                    $set("co_applicant.{$field}", $value);
                }
            });
    }

    /**
     * @return array<int, mixed>
     */
    private static function partyFields(string $namespace, bool $strictRequired): array
    {
        return [
            TextInput::make("{$namespace}.full_name")
                ->label('Имена')
                ->required($strictRequired)
                ->maxLength(255),
            TextInput::make("{$namespace}.egn")
                ->label('ЕГН')
                ->required($strictRequired)
                ->maxLength(10),
            Textarea::make("{$namespace}.permanent_address")
                ->label('Адрес')
                ->required($strictRequired)
                ->rows(2)
                ->columnSpanFull(),
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make("{$namespace}.id_card_number")
                        ->label('Лична Карта')
                        ->required($strictRequired)
                        ->maxLength(32),
                    static::datePickerField("{$namespace}.id_card_issued_at", 'Дата на Издаване')
                        ->required($strictRequired),
                    TextInput::make("{$namespace}.id_card_issued_by")
                        ->label('Издадена от')
                        ->required($strictRequired)
                        ->maxLength(255),
                ]),
        ];
    }

    private static function datePickerField(string $name, string $label): DatePicker
    {
        return DatePicker::make($name)
            ->label($label)
            ->format('Y-m-d')
            ->displayFormat('d.m.Y')
            ->placeholder('дд.мм.гггг')
            ->locale('bg')
            ->native(false);
    }

    private static function euroAmountField(string $name, string $label, ?string $placeholder = null): TextInput
    {
        $field = TextInput::make($name)
            ->label($label)
            ->numeric()
            ->inputMode('decimal')
            ->minValue(0)
            ->step('0.01')
            ->suffix('€');

        if ($placeholder !== null) {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    private static function countField(string $name, string $label, ?string $placeholder = null): TextInput
    {
        $field = TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->suffix('броя');

        if ($placeholder !== null) {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    /**
     * @param  string|array<int, string>  $layouts
     */
    private static function isLayout(Get $get, string|array $layouts): bool
    {
        $current = $get('document_layout');

        if (! is_string($current) || $current === '') {
            return false;
        }

        return in_array($current, (array) $layouts, true);
    }
}
