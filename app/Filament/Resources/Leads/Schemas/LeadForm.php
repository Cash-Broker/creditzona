<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Widgets\LeadCommunicationWidget;
use App\Models\AdminDocument;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Rules\CyrillicText;
use App\Rules\ExclusiveLeadParticipantPhone;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class LeadForm
{
    public static function mutateSubmittedData(array $data): array
    {
        if (array_key_exists('full_name', $data)) {
            [$firstName, $middleName, $lastName] = static::splitFullName($data['full_name'] ?? null);

            $data['first_name'] = $firstName;
            $data['middle_name'] = $middleName;
            $data['last_name'] = $lastName;

            unset($data['full_name']);
        }

        return $data;
    }

    public static function configure(Schema $schema, bool $includeCommunicationWidget = false): Schema
    {
        return $schema
            ->components([
                Section::make('Данни за клиента')
                    ->columnSpanFull()
                    ->columns(6)
                    ->schema([
                        Select::make('assigned_user_id')
                            ->label('Основен служител')
                            ->options(LeadResource::getPrimaryAssignmentOptions())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->different('additional_user_id')
                            ->disableOptionWhen(fn (mixed $value, Get $get): bool => (string) $get('additional_user_id') === (string) $value)
                            ->columnStart(3)
                            ->columnSpan(2),
                        Select::make('additional_user_id')
                            ->label('Допълнителен служител')
                            ->options(LeadResource::getAdditionalAssignmentOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Няма')
                            ->different('assigned_user_id')
                            ->disableOptionWhen(fn (mixed $value, Get $get): bool => (string) $get('assigned_user_id') === (string) $value)
                            ->columnSpan(2),
                        TextInput::make('full_name')
                            ->label('Имена')
                            ->required()
                            ->maxLength(180)
                            ->afterStateHydrated(function (TextInput $component, ?Lead $record): void {
                                $component->state(static::composeFullName(
                                    $record?->first_name,
                                    $record?->middle_name,
                                    $record?->last_name,
                                ));
                            })
                            ->helperText('Въведете имената на кирилица.')
                            ->rule(static fn (): Closure => static::fullNameRule())
                            ->columnSpan(4),
                        TextInput::make('egn')
                            ->label('ЕГН')
                            ->autocomplete('off')
                            ->stripCharacters([' ', '-'])
                            ->rule('digits:10')
                            ->minLength(10)
                            ->maxLength(10)
                            ->required(fn (Get $get): bool => static::requiresFullApplication($get('status')))
                            ->columnSpan(2),
                        Select::make('marital_status')
                            ->label('Семейно положение')
                            ->options(LeadResource::getMaritalStatusOptions())
                            ->nullable()
                            ->native(false)
                            ->columnSpan(2),
                        TextInput::make('city')
                            ->label('Адрес')
                            ->nullable()
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Адресът'))
                            ->helperText('Не използвайте латински букви.')
                            ->columnSpan(4),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->nullable()
                            ->rule(static fn (Get $get): Closure => static::applicantPhoneExclusivityRule($get))
                            ->maxLength(30)
                            ->columnSpan(2),
                        TextInput::make('email')
                            ->label('Имейл')
                            ->email()
                            ->nullable()
                            ->maxLength(120)
                            ->columnSpan(4),
                        TextInput::make('workplace')
                            ->label('Работодател')
                            ->nullable()
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Местоработата'))
                            ->helperText('Не използвайте латински букви.')
                            ->columnSpan(3),
                        TextInput::make('job_title')
                            ->label('Длъжност')
                            ->nullable()
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Длъжността'))
                            ->helperText('Не използвайте латински букви.')
                            ->columnSpan(3),
                        TextInput::make('salary')
                            ->label('Месечен доход')
                            ->nullable()
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->suffix('€')
                            ->columnSpan(2),
                        Select::make('credit_type')
                            ->label('Тип кредит')
                            ->options(LeadResource::getCreditTypeOptions())
                            ->required()
                            ->native(false)
                            ->live()
                            ->columnSpan(2)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state !== Lead::CREDIT_TYPE_MORTGAGE) {
                                    $set('property_type', null);
                                    $set('property_location', null);
                                }
                            }),
                        Select::make('status')
                            ->label('Статус')
                            ->options(LeadResource::getStatusOptions())
                            ->required()
                            ->default('new')
                            ->live()
                            ->native(false)
                            ->columnSpan(2),
                        TextInput::make('amount')
                            ->label('Сума')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(5000)
                            ->maxValue(50000)
                            ->suffix('€')
                            ->columnSpan(2),
                        TextInput::make('children_under_18')
                            ->label('Деца под 18')
                            ->nullable()
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->columnSpan(2),
                        TextInput::make('salary_bank')
                            ->label('Банка, в която влиза заплатата')
                            ->nullable()
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Банката за заплатата'))
                            ->helperText('Не използвайте латински букви.')
                            ->columnSpan(4),
                        TextInput::make('credit_bank')
                            ->label('Банка по кредита')
                            ->nullable()
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Банката по кредита'))
                            ->helperText('Не използвайте латински букви.')
                            ->columnSpan(4),
                        FileUpload::make('documents')
                            ->label('Документи към клиента')
                            ->disk('local')
                            ->visibility('private')
                            ->directory(fn (Lead $record): string => "lead-documents/{$record->getKey()}")
                            ->storeFileNamesIn('document_file_names')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->multiple()
                            ->appendFiles()
                            ->downloadable()
                            ->openable()
                            ->panelLayout('integrated')
                            ->maxFiles(15)
                            ->maxSize(10240)
                            ->helperText('PDF, DOC, DOCX, XLS, XLSX, JPG, PNG и WEBP до 10 MB.')
                            ->deleteUploadedFileUsing(static function (string $file): void {
                                Storage::disk('local')->delete($file);
                            })
                            ->columnSpan(2),
                    ]),
                Section::make('Поръчители')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('guarantors')
                            ->label('Поръчители')
                            ->relationship('guarantors')
                            ->defaultItems(0)
                            ->addActionLabel('Добави поръчител')
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed()
                            ->mutateRelationshipDataBeforeFillUsing(static fn (array $data): array => static::mutateGuarantorRelationshipDataForFill($data))
                            ->mutateRelationshipDataBeforeCreateUsing(static fn (array $data): ?array => static::mutateGuarantorRelationshipDataForSave($data))
                            ->mutateRelationshipDataBeforeSaveUsing(static fn (array $data): ?array => static::mutateGuarantorRelationshipDataForSave($data))
                            ->itemLabel(fn (array $state): HtmlString => static::guarantorItemLabel($state))
                            ->grid(1)
                            ->schema(static::guarantorSchema())
                            ->columnSpanFull(),
                    ]),
                Section::make('Вътрешна бележка')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('internal_notes')
                            ->label('Вътрешна бележка')
                            ->rows(8)
                            ->autosize()
                            ->afterStateHydrated(function (Textarea $component, mixed $state): void {
                                $component->state(static::normalizeLegacyNoteForTextarea(
                                    is_string($state) ? $state : null,
                                ));
                            })
                            ->dehydrateStateUsing(static fn (?string $state): ?string => filled(trim((string) $state)) ? trim((string) $state) : null)
                            ->columnSpanFull(),
                    ]),
                Section::make('Данни за имота')
                    ->columnSpanFull()
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('credit_type') === Lead::CREDIT_TYPE_MORTGAGE)
                    ->schema([
                        Select::make('property_type')
                            ->label('Тип имот')
                            ->options(LeadResource::getPropertyTypeOptions())
                            ->native(false)
                            ->nullable(),
                        TextInput::make('property_location')
                            ->label('Местоположение на имота')
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Местоположението на имота'))
                            ->helperText('Не използвайте латински букви.')
                            ->nullable(),
                    ]),
                static::communicationSection()
                    ->visible($includeCommunicationWidget),
            ]);
    }

    /**
     * @return array<int, Section>
     */
    private static function guarantorSchema(): array
    {
        return [
            Section::make('Данни за поръчителя')
                ->columns(6)
                ->schema([
                    Select::make('status')
                        ->label('Статус')
                        ->options(LeadResource::getGuarantorStatusOptions())
                        ->nullable()
                        ->native(false)
                        ->columnSpan(2),
                    TextInput::make('amount')
                        ->label('Сума')
                        ->nullable()
                        ->numeric()
                        ->integer()
                        ->minValue(5000)
                        ->maxValue(50000)
                        ->suffix('€')
                        ->columnSpan(2),
                    Hidden::make('first_name'),
                    Hidden::make('middle_name'),
                    Hidden::make('last_name'),
                    TextInput::make('full_name')
                        ->label('Имена')
                        ->required(fn (Get $get): bool => static::guarantorRequiresIdentityFields($get))
                        ->maxLength(180)
                        ->rule(CyrillicText::withoutLatin('Имената на поръчителя'))
                        ->helperText('Въведете имената на кирилица.')
                        ->columnSpan(4),
                    TextInput::make('egn')
                        ->label('ЕГН')
                        ->autocomplete('off')
                        ->stripCharacters([' ', '-'])
                        ->rule('digits:10')
                        ->minLength(10)
                        ->maxLength(10)
                        ->columnSpan(2),
                    Select::make('marital_status')
                        ->label('Семейно положение')
                        ->options(LeadResource::getMaritalStatusOptions())
                        ->nullable()
                        ->native(false)
                        ->columnSpan(2),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->nullable()
                        ->rule(static fn (Get $get): Closure => static::guarantorPhoneExclusivityRule($get))
                        ->maxLength(30)
                        ->columnSpan(2),
                    TextInput::make('city')
                        ->label('Адрес')
                        ->nullable()
                        ->maxLength(120)
                        ->rule(CyrillicText::withoutLatin('Адресът на поръчителя'))
                        ->helperText('Не използвайте латински букви.')
                        ->columnSpan(4),
                    TextInput::make('email')
                        ->label('Имейл')
                        ->email()
                        ->nullable()
                        ->maxLength(120)
                        ->columnSpan(4),
                    TextInput::make('workplace')
                        ->label('Работодател')
                        ->nullable()
                        ->maxLength(120)
                        ->rule(CyrillicText::withoutLatin('Местоработата на поръчителя'))
                        ->helperText('Не използвайте латински букви.')
                        ->columnSpan(3),
                    TextInput::make('job_title')
                        ->label('Длъжност')
                        ->nullable()
                        ->maxLength(120)
                        ->rule(CyrillicText::withoutLatin('Длъжността на поръчителя'))
                        ->helperText('Не използвайте латински букви.')
                        ->columnSpan(3),
                    TextInput::make('salary')
                        ->label('Месечен доход')
                        ->nullable()
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->suffix('€')
                        ->columnSpan(2),
                    TextInput::make('children_under_18')
                        ->label('Деца под 18')
                        ->nullable()
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->columnSpan(2),
                    TextInput::make('salary_bank')
                        ->label('Банка, в която влиза заплатата')
                        ->nullable()
                        ->maxLength(120)
                        ->rule(CyrillicText::withoutLatin('Банката за заплатата на поръчителя'))
                        ->helperText('Не използвайте латински букви.')
                        ->columnSpan(4),
                    TextInput::make('credit_bank')
                        ->label('Банка по кредита')
                        ->nullable()
                        ->maxLength(120)
                        ->rule(CyrillicText::withoutLatin('Банката по кредита на поръчителя'))
                        ->helperText('Не използвайте латински букви.')
                        ->columnSpan(4),
                    FileUpload::make('documents')
                        ->label('Документи към поръчителя')
                        ->disk('local')
                        ->visibility('private')
                        ->directory('lead-guarantor-documents')
                        ->storeFileNamesIn('document_file_names')
                        ->acceptedFileTypes(AdminDocument::getSafeUploadMimeTypes())
                        ->multiple()
                        ->appendFiles()
                        ->downloadable()
                        ->openable()
                        ->panelLayout('integrated')
                        ->maxFiles(15)
                        ->maxSize(10240)
                        ->helperText('PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, WEBP и GIF до 10 MB.')
                        ->deleteUploadedFileUsing(static function (string $file): void {
                            Storage::disk('local')->delete($file);
                        })
                        ->columnSpan(2),
                ]),
            Section::make('Вътрешна бележка за поръчителя')
                ->schema([
                    Textarea::make('internal_notes')
                        ->label('Бележка')
                        ->rows(8)
                        ->autosize()
                        ->afterStateHydrated(function (Textarea $component, mixed $state): void {
                            $component->state(static::normalizeLegacyNoteForTextarea(
                                is_string($state) ? $state : null,
                            ));
                        })
                        ->dehydrateStateUsing(static fn (?string $state): ?string => filled(trim((string) $state)) ? trim((string) $state) : null)
                        ->columnSpanFull(),
                ]),
            Section::make('Данни за имота на поръчителя')
                ->columns(2)
                ->visible(fn (Get $get): bool => static::guarantorHasPropertyData($get))
                ->schema([
                    Select::make('property_type')
                        ->label('Тип имот')
                        ->options(LeadResource::getPropertyTypeOptions())
                        ->native(false)
                        ->nullable(),
                    TextInput::make('property_location')
                        ->label('Местоположение на имота')
                        ->nullable()
                        ->maxLength(120)
                        ->rule(CyrillicText::withoutLatin('Местоположението на имота на поръчителя'))
                        ->helperText('Не използвайте латински букви.'),
                ]),
        ];
    }

    private static function communicationSection(): Section
    {
        return Section::make('Комуникация')
            ->schema([
                LivewireComponent::make(LeadCommunicationWidget::class)
                    ->key('attached-lead-communication-widget')
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    private static function requiresFullApplication(?string $status): bool
    {
        return ! in_array($status, ['sms', 'email', 'rejected'], true);
    }

    private static function guarantorHasPropertyData(Get $get): bool
    {
        return $get('../../credit_type') === Lead::CREDIT_TYPE_MORTGAGE;
    }

    private static function guarantorRequiresIdentityFields(Get $get): bool
    {
        return static::hasMeaningfulGuarantorData([
            $get('amount'),
            $get('full_name'),
            $get('first_name'),
            $get('middle_name'),
            $get('last_name'),
            $get('egn'),
            $get('phone'),
            $get('email'),
            $get('city'),
            $get('workplace'),
            $get('job_title'),
            $get('salary'),
            $get('marital_status'),
            $get('children_under_18'),
            $get('salary_bank'),
            $get('credit_bank'),
            $get('property_type'),
            $get('property_location'),
            $get('documents'),
            $get('document_file_names'),
            $get('internal_notes'),
        ]);
    }

    private static function guarantorItemLabel(array $state): HtmlString
    {
        $name = trim(
            ($state['full_name'] ?? '') !== ''
                ? (string) $state['full_name']
                : static::composeFullName(
                    $state['first_name'] ?? null,
                    $state['middle_name'] ?? null,
                    $state['last_name'] ?? null,
                ),
        );

        $status = $state['status'] ?? null;
        $statusLabel = LeadGuarantor::getStatusLabel($status);
        $label = $name !== '' ? "{$statusLabel} • {$name}" : $statusLabel;

        return new HtmlString(sprintf(
            '<span class="%s">%s</span>',
            e(LeadGuarantor::getItemLabelClasses($status)),
            e($label !== '' ? $label : 'Нов поръчител'),
        ));
    }

    private static function pruneBlankGuarantor(array $data): ?array
    {
        return static::hasMeaningfulGuarantorData(Arr::except($data, ['lead_id', 'status']))
            ? $data
            : null;
    }

    private static function mutateGuarantorRelationshipDataForFill(array $data): array
    {
        $data['full_name'] = static::composeFullName(
            $data['first_name'] ?? null,
            $data['middle_name'] ?? null,
            $data['last_name'] ?? null,
        );

        return $data;
    }

    private static function mutateGuarantorRelationshipDataForSave(array $data): ?array
    {
        if (array_key_exists('full_name', $data)) {
            [$firstName, $middleName, $lastName] = static::splitFullName($data['full_name'] ?? null);

            $data['first_name'] = $firstName;
            $data['middle_name'] = $middleName;
            $data['last_name'] = $lastName;

            unset($data['full_name']);
        }

        return static::pruneBlankGuarantor($data);
    }

    private static function hasMeaningfulGuarantorData(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (static::hasMeaningfulGuarantorData($item)) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($value)) {
            return static::extractVisibleText($value) !== '';
        }

        return filled($value);
    }

    private static function normalizeRichTextState(?string $state): ?string
    {
        return static::extractVisibleText($state) === ''
            ? null
            : $state;
    }

    private static function fullNameRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            static::validateFullName($value, $fail);
        };
    }

    private static function validateFullName(mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $parts = static::extractNameParts($value);

        if (count($parts) < 2) {
            $fail('Моля, въведете поне име и фамилия.');

            return;
        }

        $rule = CyrillicText::lettersOnly('Имената');

        foreach ($parts as $part) {
            $rule->validate('full_name', $part, $fail);
        }
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private static function splitFullName(?string $value): array
    {
        $parts = static::extractNameParts($value);

        if ($parts === []) {
            return [null, null, null];
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts) ?? null;
        $middleName = $parts !== [] ? implode(' ', $parts) : null;

        return [$firstName, $middleName, $lastName];
    }

    private static function composeFullName(?string $firstName, ?string $middleName, ?string $lastName): string
    {
        return trim(implode(' ', array_filter([
            $firstName,
            $middleName,
            $lastName,
        ])));
    }

    /**
     * @return array<int, string>
     */
    private static function extractNameParts(?string $value): array
    {
        $normalized = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $normalized)));
    }

    private static function extractVisibleText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = strip_tags($value);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private static function normalizeLegacyNoteForTextarea(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = preg_replace('/<br\s*\/?>/iu', "\n", $value) ?? $value;
        $text = preg_replace('/<\/p>/iu', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\/div>/iu', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
        $text = preg_replace("/\r\n|\r/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
        $text = trim($text);

        return $text !== '' ? $text : null;
    }

    private static function applicantPhoneExclusivityRule(Get $get): Closure
    {
        $rule = ExclusiveLeadParticipantPhone::forApplicant();

        return static function (string $attribute, mixed $value, Closure $fail) use ($rule): void {
            $rule->validate($attribute, $value, $fail);
        };
    }

    private static function guarantorPhoneExclusivityRule(Get $get): Closure
    {
        $rule = ExclusiveLeadParticipantPhone::forGuarantor([$get('../../phone')]);

        return static function (string $attribute, mixed $value, Closure $fail) use ($rule): void {
            $rule->validate($attribute, $value, $fail);
        };
    }
}
