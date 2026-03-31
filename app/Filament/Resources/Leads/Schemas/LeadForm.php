<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Widgets\LeadCommunicationWidget;
use App\Models\AdminDocument;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Rules\CyrillicText;
use App\Rules\ExclusiveLeadParticipantPhone;
use App\Support\Notes\NoteHistory;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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
    public static function mutateSubmittedData(array $data, ?Lead $record = null): array
    {
        if (array_key_exists('full_name', $data)) {
            [$firstName, $middleName, $lastName] = static::splitFullName($data['full_name'] ?? null);

            $data['first_name'] = $firstName;
            $data['middle_name'] = $middleName;
            $data['last_name'] = $lastName;

            unset($data['full_name']);
        }

        $leadNoteEntries = is_array($data['lead_note_entries'] ?? null)
            ? $data['lead_note_entries']
            : NoteHistory::formEntries($record?->internal_notes);

        $data['internal_notes'] = static::buildNoteHistoryState(
            $record?->internal_notes,
            $leadNoteEntries,
            is_string($data['lead_new_internal_note'] ?? null) ? $data['lead_new_internal_note'] : null,
        );

        unset($data['lead_note_entries']);
        unset($data['lead_new_internal_note']);

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
                            ->columnSpan(3),
                        TextInput::make('job_title')
                            ->label('Длъжност')
                            ->nullable()
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Длъжността'))
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
                            ->columnSpan(4),
                        TextInput::make('credit_bank')
                            ->label('Банка по кредита')
                            ->nullable()
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Банката по кредита'))
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
                            ->panelLayout('compact')
                            ->maxFiles(15)
                            ->maxSize(10240)
                            ->deleteUploadedFileUsing(static function (string $file): void {
                                Storage::disk('local')->delete($file);
                            })
                            ->columnSpanFull(),
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
                Section::make('Съобщения за клиента')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('lead_note_chat_display')
                            ->hiddenLabel()
                            ->content(fn (?Lead $record): HtmlString => static::renderNoteChatBubbles(
                                is_string($record?->internal_notes) ? $record->internal_notes : null,
                                'lead_note_entries',
                            ))
                            ->columnSpanFull(),
                        Repeater::make('lead_note_entries')
                            ->hiddenLabel()
                            ->afterStateHydrated(function (Repeater $component, mixed $state, ?Lead $record): void {
                                $component->state(NoteHistory::formEntries(
                                    is_string($record?->internal_notes) ? $record->internal_notes : null,
                                ));
                            })
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->dehydrated()
                            ->schema(static::noteEntrySchema())
                            ->extraAttributes(['class' => '!hidden'])
                            ->columnSpanFull(),
                        Placeholder::make('lead_note_input_bar')
                            ->hiddenLabel()
                            ->content(new HtmlString(static::renderNoteInputBar(
                                'lead_new_internal_note',
                                'lead_note_entries',
                                'lead_note_chat_display',
                            )))
                            ->columnSpanFull(),
                        Textarea::make('lead_new_internal_note')
                            ->hiddenLabel()
                            ->afterStateHydrated(static fn (Textarea $component): Textarea => $component->state(null))
                            ->dehydrateStateUsing(static fn (?string $state): ?string => filled(trim((string) $state)) ? trim((string) $state) : null)
                            ->extraAttributes(['class' => '!hidden'])
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
                    Placeholder::make('privacy_consent_declaration_download')
                        ->label('Декларация за съгласие')
                        ->content(fn (?LeadGuarantor $record): HtmlString => static::renderGuarantorPrivacyConsentAction($record))
                        ->columnStart(5)
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
                        ->columnSpan(3),
                    TextInput::make('job_title')
                        ->label('Длъжност')
                        ->nullable()
                        ->maxLength(120)
                        ->rule(CyrillicText::withoutLatin('Длъжността на поръчителя'))
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
                        ->columnSpan(4),
                    TextInput::make('credit_bank')
                        ->label('Банка по кредита')
                        ->nullable()
                        ->maxLength(120)
                        ->rule(CyrillicText::withoutLatin('Банката по кредита на поръчителя'))
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
                        ->panelLayout('compact')
                        ->maxFiles(15)
                        ->maxSize(10240)
                        ->deleteUploadedFileUsing(static function (string $file): void {
                            Storage::disk('local')->delete($file);
                        })
                        ->columnSpanFull(),
                    Hidden::make('existing_internal_notes')
                        ->dehydrated()
                        ->afterStateHydrated(function (Hidden $component, mixed $state, ?LeadGuarantor $record): void {
                            $component->state(NoteHistory::normalize(
                                is_string($record?->internal_notes) ? $record->internal_notes : null,
                            ));
                        }),
                    Placeholder::make('guarantor_note_chat_display')
                        ->hiddenLabel()
                        ->content(fn (?LeadGuarantor $record): HtmlString => static::renderNoteChatBubbles(
                            is_string($record?->internal_notes) ? $record->internal_notes : null,
                            'internal_note_entries',
                        ))
                        ->columnSpanFull(),
                    Repeater::make('internal_note_entries')
                        ->hiddenLabel()
                        ->afterStateHydrated(function (Repeater $component, mixed $state, ?LeadGuarantor $record): void {
                            $component->state(NoteHistory::formEntries(
                                is_string($record?->internal_notes) ? $record->internal_notes : null,
                            ));
                        })
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->dehydrated()
                        ->schema(static::noteEntrySchema())
                        ->extraAttributes(['class' => '!hidden'])
                        ->columnSpanFull(),
                    Placeholder::make('guarantor_note_input_bar')
                        ->hiddenLabel()
                        ->content(fn (?LeadGuarantor $record): HtmlString => new HtmlString(static::renderNoteInputBar(
                            'new_internal_note',
                            'internal_note_entries',
                            'guarantor_note_chat_display',
                            $record?->id,
                        )))
                        ->columnSpanFull(),
                    Textarea::make('new_internal_note')
                        ->hiddenLabel()
                        ->afterStateHydrated(static fn (Textarea $component): Textarea => $component->state(null))
                        ->dehydrateStateUsing(static fn (?string $state): ?string => filled(trim((string) $state)) ? trim((string) $state) : null)
                        ->extraAttributes(['class' => '!hidden'])
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
                        ->rule(CyrillicText::withoutLatin('Местоположението на имота на поръчителя')),
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

    private static function renderGuarantorPrivacyConsentAction(?LeadGuarantor $record): HtmlString
    {
        if (! $record?->exists || $record->lead_id === null) {
            return new HtmlString(
                '<div class="text-xs text-gray-500">Запазете заявката, за да генерирате декларацията.</div>',
            );
        }

        $url = route('admin.leads.guarantors.privacy-consent.download', [
            'lead' => $record->lead_id,
            'guarantor' => $record,
        ]);

        return new HtmlString(sprintf(
            '<a href="%s" class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-primary-500/20 dark:bg-white/5 dark:text-primary-300 dark:hover:bg-primary-500/10 dark:hover:text-primary-200 dark:focus:ring-offset-gray-950">Генерирай декларация</a>',
            e($url),
        ));
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
            $get('new_internal_note'),
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
        $data['existing_internal_notes'] = NoteHistory::normalize(
            is_string($data['internal_notes'] ?? null) ? $data['internal_notes'] : null,
        );
        $data['internal_note_entries'] = NoteHistory::formEntries(
            is_string($data['internal_notes'] ?? null) ? $data['internal_notes'] : null,
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

        unset($data['existing_internal_notes']);
        unset($data['internal_note_entries']);
        unset($data['new_internal_note']);

        return static::pruneBlankGuarantor($data);
    }

    /**
     * @param  array<string, mixed>  $rawState
     * @return array<int, array{id: int|null, fingerprint: string, existing_notes: ?string, entries: array<int, array<string, mixed>>, note: ?string}>
     */
    public static function captureGuarantorNoteDrafts(array $rawState): array
    {
        $guarantors = $rawState['guarantors'] ?? [];

        if (! is_array($guarantors)) {
            return [];
        }

        $drafts = [];

        foreach ($guarantors as $guarantor) {
            if (! is_array($guarantor)) {
                continue;
            }

            $existingNotes = is_string($guarantor['existing_internal_notes'] ?? null)
                ? $guarantor['existing_internal_notes']
                : null;

            $entries = is_array($guarantor['internal_note_entries'] ?? null)
                ? $guarantor['internal_note_entries']
                : NoteHistory::formEntries($existingNotes);

            $note = NoteHistory::normalize(
                is_string($guarantor['new_internal_note'] ?? null) ? $guarantor['new_internal_note'] : null,
            );

            if ($entries === [] && $note === null) {
                continue;
            }

            $drafts[] = [
                'id' => isset($guarantor['id']) ? (int) $guarantor['id'] : null,
                'fingerprint' => static::buildGuarantorFingerprint($guarantor),
                'existing_notes' => $existingNotes,
                'entries' => $entries,
                'note' => $note,
            ];
        }

        return $drafts;
    }

    /**
     * @param  array<int, array{id: int|null, fingerprint: string, existing_notes: ?string, entries: array<int, array<string, mixed>>, note: ?string}>  $drafts
     */
    public static function persistGuarantorNoteDrafts(Lead $lead, array $drafts): void
    {
        if ($drafts === []) {
            return;
        }

        $guarantors = $lead->guarantors()->get();

        foreach ($drafts as $draft) {
            $guarantor = null;

            if ($draft['id'] !== null) {
                $guarantor = $guarantors->firstWhere('id', $draft['id']);
            }

            if (! $guarantor instanceof LeadGuarantor) {
                $guarantor = $guarantors->first(
                    fn (LeadGuarantor $candidate): bool => static::buildGuarantorFingerprint([
                        'full_name' => static::composeFullName(
                            $candidate->first_name,
                            $candidate->middle_name,
                            $candidate->last_name,
                        ),
                        'phone' => $candidate->phone,
                        'egn' => $candidate->egn,
                    ]) === $draft['fingerprint'],
                );
            }

            if (! $guarantor instanceof LeadGuarantor) {
                continue;
            }

            $guarantor->forceFill([
                'internal_notes' => static::buildNoteHistoryState(
                    is_string($guarantor->internal_notes) ? $guarantor->internal_notes : $draft['existing_notes'],
                    is_array($draft['entries']) ? $draft['entries'] : [],
                    is_string($draft['note'] ?? null) ? $draft['note'] : null,
                ),
            ])->save();
        }
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

    /**
     * @param  array<string, mixed>  $data
     */
    private static function buildGuarantorFingerprint(array $data): string
    {
        return implode('|', [
            mb_strtolower(trim((string) ($data['full_name'] ?? ''))),
            preg_replace('/\D+/', '', (string) ($data['phone'] ?? '')) ?? '',
            preg_replace('/\D+/', '', (string) ($data['egn'] ?? '')) ?? '',
        ]);
    }

    private static function appendNoteHistory(?string $existingNotes, ?string $newNote): ?string
    {
        if ($newNote === null) {
            return $existingNotes;
        }

        $authorName = trim((string) auth()->user()?->name) ?: 'Служител';
        $timestamp = now('Europe/Sofia')->format('d.m.Y H:i');
        $entry = sprintf('[%s] %s: %s', $timestamp, $authorName, $newNote);

        if ($existingNotes === null) {
            return $entry;
        }

        return $existingNotes."\n\n".$entry;
    }

    private static function renderNoteHistory(?string $notes, string $emptyMessage): HtmlString
    {
        if ($notes === null) {
            return new HtmlString(sprintf(
                '<div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500">%s</div>',
                e($emptyMessage),
            ));
        }

        return new HtmlString(sprintf(
            '<div class="whitespace-pre-wrap rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-700">%s</div>',
            nl2br(e($notes)),
        ));
    }

    private static function renderChatLikeNoteHistory(?string $notes, string $emptyMessage): HtmlString
    {
        $entries = NoteHistory::entries($notes);

        if ($entries === []) {
            return new HtmlString(sprintf(
                '<div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500">%s</div>',
                e($emptyMessage),
            ));
        }

        $content = implode('', array_map(static function (array $entry): string {
            $metaParts = array_values(array_filter([
                $entry['author'],
                $entry['timestamp'],
            ]));

            $meta = $metaParts !== []
                ? sprintf(
                    '<div class="mb-2 text-xs font-semibold text-gray-500">%s</div>',
                    e(implode(' • ', $metaParts)),
                )
                : '';

            return sprintf(
                '<div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">%s<div class="whitespace-pre-wrap text-sm leading-6 text-gray-700">%s</div></div>',
                $meta,
                nl2br(e($entry['body'])),
            );
        }, $entries));

        return new HtmlString(sprintf(
            '<div class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50 p-3">%s</div>',
            $content,
        ));
    }

    private static function renderNoteChatBubbles(?string $notes, string $repeaterName = 'lead_note_entries'): HtmlString
    {
        $entries = NoteHistory::entries($notes);

        if ($entries === []) {
            return new HtmlString(
                '<div class="flex h-96 items-center justify-center rounded-xl border border-gray-200 bg-gray-50/50 dark:border-white/10 dark:bg-gray-900/40">'
                .'<span class="text-sm text-gray-400 dark:text-gray-500">Няма съобщения</span>'
                .'</div>',
            );
        }

        $currentUserId = auth()->id();
        $currentUserName = auth()->user()?->name;
        $bubbles = '';

        foreach ($entries as $index => $entry) {
            $authorId = $entry['author_id'] ?? null;
            $authorName = $entry['author'] ?? 'Служител';

            $isMe = ($authorId !== null && $currentUserId !== null && $authorId === $currentUserId)
                || (
                    $currentUserName !== null
                    && mb_strtolower(trim($authorName)) === mb_strtolower(trim($currentUserName))
                );

            $canEdit = $isMe && NoteHistory::canEditEntry($entry, $currentUserId, $currentUserName);

            $letter = e(mb_strtoupper(mb_substr($authorName, 0, 1)));
            $displayName = e($authorName);
            $time = e($entry['timestamp'] ?? '');
            $rawBody = $entry['body'];
            $body = nl2br(e($rawBody));

            $editedTag = '';

            if (filled($entry['edited_at'] ?? null) || filled($entry['edited_by'] ?? null)) {
                $editParts = array_filter([$entry['edited_by'] ?? null, $entry['edited_at'] ?? null]);
                $editedTag = '<div class="mt-1 text-[10px] opacity-60">редактирано '.e(implode(' • ', $editParts)).'</div>';
            }

            $alignClass = $isMe ? 'justify-end' : '';
            $rowClass = $isMe ? 'flex-row-reverse' : '';
            $avatarClass = $isMe
                ? 'bg-primary-600 text-white'
                : 'bg-white text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10';
            $metaClass = $isMe
                ? 'justify-end text-primary-600 dark:text-primary-400'
                : 'text-gray-400 dark:text-gray-500';
            $bubbleClass = $isMe
                ? 'rounded-br-sm bg-primary-600 text-white'
                : 'rounded-bl-sm bg-white text-gray-800 ring-1 ring-gray-200 dark:bg-gray-950 dark:text-gray-100 dark:ring-white/10';

            $escapedRepeaterName = e($repeaterName);
            $escapedBody = e(json_encode($rawBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            if ($canEdit) {
                $bubbleContent = ''
                    .'<div x-show="!editing" class="group relative cursor-pointer" @click="editing = true">'
                    .'<div class="whitespace-pre-wrap wrap-break-word">'.$body.'</div>'
                    .$editedTag
                    .'<div class="absolute -top-1 '.($isMe ? '-left-6' : '-right-6').' hidden rounded-full bg-gray-800 p-0.5 text-white group-hover:block dark:bg-gray-200 dark:text-gray-800">'
                    .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path d="M13.488 2.513a1.75 1.75 0 0 0-2.475 0L6.75 6.774a2.75 2.75 0 0 0-.596.892l-.848 2.047a.75.75 0 0 0 .98.98l2.047-.848a2.75 2.75 0 0 0 .892-.596l4.261-4.262a1.75 1.75 0 0 0 0-2.474Z"/><path d="M4.75 3.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h6.5c.69 0 1.25-.56 1.25-1.25V9A.75.75 0 0 1 14 9v2.25A2.75 2.75 0 0 1 11.25 14h-6.5A2.75 2.75 0 0 1 2 11.25v-6.5A2.75 2.75 0 0 1 4.75 2H7a.75.75 0 0 1 0 1.5H4.75Z"/></svg>'
                    .'</div>'
                    .'</div>'
                    .'<div x-show="editing" x-cloak>'
                    .'<textarea x-model="body" rows="2" class="w-full rounded-lg border-0 bg-white/20 p-1 text-sm leading-relaxed focus:ring-1 focus:ring-white/40 '.($isMe ? 'text-white placeholder-white/60' : 'text-gray-800 dark:text-gray-100').'"></textarea>'
                    .'<div class="mt-1 flex gap-1">'
                    .'<button type="button" @click="'
                    .'let keys = Object.keys($wire.data.'.$escapedRepeaterName.'); '
                    .'if (keys['.$index.']) $wire.set(\'data.'.$escapedRepeaterName.'.\' + keys['.$index.'] + \'.body\', body); '
                    .'editing = false'
                    .'" class="rounded px-2 py-0.5 text-[11px] font-medium '.($isMe ? 'bg-white/20 text-white hover:bg-white/30' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200').'">Запази</button>'
                    .'<button type="button" @click="body = original; editing = false" class="rounded px-2 py-0.5 text-[11px] font-medium '.($isMe ? 'text-white/70 hover:text-white' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300').'">Откажи</button>'
                    .'</div>'
                    .'</div>';

                $bubbles .= '<div class="flex '.$alignClass.'" x-data="{ editing: false, body: '.$escapedBody.', original: '.$escapedBody.' }">'
                    .'<div class="flex max-w-[80%] items-end gap-2 '.$rowClass.'">'
                    .'<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold '.$avatarClass.'">'.$letter.'</div>'
                    .'<div class="min-w-0">'
                    .'<div class="mb-0.5 flex items-center gap-1.5 text-[11px] '.$metaClass.'">'
                    .'<span class="font-medium">'.$displayName.'</span>'
                    .'<span>'.$time.'</span>'
                    .'</div>'
                    .'<div class="min-w-16 rounded-2xl px-3 py-2 text-sm leading-relaxed '.$bubbleClass.'">'
                    .$bubbleContent
                    .'</div>'
                    .'</div>'
                    .'</div>'
                    .'</div>';
            } else {
                $bubbles .= '<div class="flex '.$alignClass.'">'
                    .'<div class="flex max-w-[80%] items-end gap-2 '.$rowClass.'">'
                    .'<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold '.$avatarClass.'">'.$letter.'</div>'
                    .'<div class="min-w-0">'
                    .'<div class="mb-0.5 flex items-center gap-1.5 text-[11px] '.$metaClass.'">'
                    .'<span class="font-medium">'.$displayName.'</span>'
                    .'<span>'.$time.'</span>'
                    .'</div>'
                    .'<div class="min-w-16 rounded-2xl px-3 py-2 text-sm leading-relaxed '.$bubbleClass.'">'
                    .'<div class="whitespace-pre-wrap wrap-break-word">'.$body.'</div>'
                    .$editedTag
                    .'</div>'
                    .'</div>'
                    .'</div>'
                    .'</div>';
            }
        }

        return new HtmlString(
            '<div class="h-96 overflow-y-auto rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3 dark:border-white/10 dark:bg-gray-900/40"'
            .' x-data x-init="$el.scrollTop = $el.scrollHeight">'
            .'<div class="space-y-3">'.$bubbles.'</div>'
            .'</div>',
        );
    }

    private static function renderNoteInputBar(string $textareaField, string $repeaterName, string $chatPlaceholder, ?int $guarantorId = null): string
    {
        $isLeadNote = $textareaField === 'lead_new_internal_note';

        $wireCall = $isLeadNote
            ? '$wire.saveNoteOnly(msg.trim())'
            : '$wire.saveGuarantorNoteOnly('.((int) $guarantorId).', msg.trim())';

        return '<div class="mt-2 flex items-end gap-2 border-t border-gray-200 pt-2 dark:border-white/10"'
            .' x-data="{ msg: \'\', sending: false }">'
            .'<textarea x-model="msg" rows="2" placeholder="Добави бележка..." '
            .'class="min-h-16 min-w-0 flex-1 resize-none rounded-xl border-gray-300 bg-gray-50/80 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900/70 dark:text-white" '
            .'x-on:input="$el.style.height = \'auto\'; $el.style.height = Math.max($el.scrollHeight, 64) + \'px\'" '
            .'x-on:keydown.enter.prevent="if (!$event.shiftKey && msg.trim() && !sending) $refs.sendBtn.click()"'
            .'></textarea>'
            .'<button type="button" x-ref="sendBtn" '
            .'class="shrink-0 self-end rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50" '
            .':disabled="!msg.trim() || sending" '
            .'@click="'
            .'if (!msg.trim() || sending) return; '
            .'sending = true; '
            .$wireCall.'.then(() => { '
            .'msg = \'\'; '
            .'sending = false; '
            .'$refs.sendBtn.previousElementSibling && ($refs.sendBtn.previousElementSibling.style.height = \'auto\'); '
            .'})'
            .'"'
            .'>Изпрати</button>'
            .'</div>';
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function noteEntrySchema(): array
    {
        return [
            Hidden::make('id'),
            Hidden::make('author'),
            Hidden::make('author_id'),
            Hidden::make('timestamp'),
            Hidden::make('edited_at'),
            Hidden::make('edited_by'),
            Placeholder::make('message_meta')
                ->label('Изпратено от')
                ->content(fn (Get $get): string => static::noteEntryItemLabel([
                    'author' => $get('author'),
                    'timestamp' => $get('timestamp'),
                ])),
            Textarea::make('body')
                ->label('Съобщение')
                ->rows(3)
                ->autosize()
                ->disabled(fn (Get $get): bool => ! static::canEditNoteEntry([
                    'author' => $get('author'),
                    'author_id' => $get('author_id'),
                ]))
                ->saved()
                ->dehydrated()
                ->required(),
            Placeholder::make('message_edit_meta')
                ->label('Последна редакция')
                ->content(fn (Get $get): ?string => static::noteEntryEditedLabel([
                    'edited_at' => $get('edited_at'),
                    'edited_by' => $get('edited_by'),
                ]))
                ->visible(fn (Get $get): bool => filled($get('edited_at')) || filled($get('edited_by'))),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function noteEntryItemLabel(array $state): string
    {
        $parts = array_values(array_filter([
            filled($state['author'] ?? null) ? (string) $state['author'] : 'Служител',
            $state['timestamp'] ?? null,
        ]));

        return $parts !== [] ? implode(' • ', $parts) : 'Съобщение';
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function noteEntryEditedLabel(array $state): ?string
    {
        $parts = array_values(array_filter([
            $state['edited_by'] ?? null,
            $state['edited_at'] ?? null,
        ]));

        if ($parts === []) {
            return null;
        }

        return implode(' • ', $parts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    private static function buildNoteHistoryState(?string $existingNotes, array $entries, ?string $newNote): ?string
    {
        $noteHistory = NoteHistory::replace(
            $existingNotes,
            $entries,
            auth()->user()?->name,
            auth()->id(),
        );

        return NoteHistory::append(
            $noteHistory,
            $newNote,
            auth()->user()?->name,
            auth()->id(),
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function canEditNoteEntry(array $state): bool
    {
        return NoteHistory::canEditEntry(
            $state,
            auth()->id(),
            auth()->user()?->name,
        );
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
