<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Rules\CyrillicText;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основни данни')
                    ->columns(3)
                    ->schema([
                        Select::make('credit_type')
                            ->label('Тип кредит')
                            ->options(LeadResource::getCreditTypeOptions())
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state !== 'mortgage') {
                                    $set('property_type', null);
                                    $set('property_location', null);
                                }
                            }),
                        Select::make('status')
                            ->label('Статус')
                            ->options(LeadResource::getStatusOptions())
                            ->required()
                            ->default('new')
                            ->native(false),
                        Select::make('assigned_user_id')
                            ->label('Основен служител')
                            ->options(LeadResource::getPrimaryAssignmentOptions())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->different('additional_user_id')
                            ->disableOptionWhen(fn (mixed $value, Get $get): bool => (string) $get('additional_user_id') === (string) $value),
                        Select::make('additional_user_id')
                            ->label('Допълнителен служител')
                            ->options(LeadResource::getAdditionalAssignmentOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Няма')
                            ->different('assigned_user_id')
                            ->disableOptionWhen(fn (mixed $value, Get $get): bool => (string) $get('assigned_user_id') === (string) $value),
                        TextInput::make('amount')
                            ->label('Сума')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(5000)
                            ->maxValue(50000)
                            ->suffix('€'),
                        TextInput::make('first_name')
                            ->label('Име')
                            ->required()
                            ->maxLength(60)
                            ->rule(CyrillicText::lettersOnly('Името'))
                            ->helperText('Пишете само на кирилица.'),
                        TextInput::make('middle_name')
                            ->label('Презиме')
                            ->maxLength(60)
                            ->rule(CyrillicText::lettersOnly('Презимето'))
                            ->helperText('Пишете само на кирилица.'),
                        TextInput::make('last_name')
                            ->label('Фамилия')
                            ->required()
                            ->maxLength(60)
                            ->rule(CyrillicText::lettersOnly('Фамилията'))
                            ->helperText('Пишете само на кирилица.'),
                        TextInput::make('egn')
                            ->label('ЕГН')
                            ->password()
                            ->revealable()
                            ->autocomplete('off')
                            ->stripCharacters([' ', '-'])
                            ->rule('digits:10')
                            ->minLength(10)
                            ->maxLength(10),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label('Имейл')
                            ->email()
                            ->required()
                            ->maxLength(120),
                        TextInput::make('city')
                            ->label('Град')
                            ->required()
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Градът'))
                            ->helperText('Не използвайте латински букви.'),
                    ]),
                Section::make('Допълнителна информация')
                    ->columns(3)
                    ->schema([
                        TextInput::make('workplace')
                            ->label('Месторабота')
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Местоработата'))
                            ->helperText('Не използвайте латински букви.'),
                        TextInput::make('job_title')
                            ->label('Длъжност')
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Длъжността'))
                            ->helperText('Не използвайте латински букви.'),
                        TextInput::make('salary')
                            ->label('Заплата')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->suffix('€'),
                        Select::make('marital_status')
                            ->label('Семейно положение')
                            ->options(LeadResource::getMaritalStatusOptions())
                            ->native(false),
                        TextInput::make('children_under_18')
                            ->label('Деца под 18')
                            ->numeric()
                            ->integer()
                            ->minValue(0),
                        TextInput::make('salary_bank')
                            ->label('Банка, в която влиза заплатата')
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Банката за заплата'))
                            ->helperText('Не използвайте латински букви.'),
                        TextInput::make('credit_bank')
                            ->label('Банка по кредита')
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Банката по кредита'))
                            ->helperText('Не използвайте латински букви.'),
                    ]),
                Section::make('Данни за имота')
                    ->columns(2)
                    ->schema([
                        Select::make('property_type')
                            ->label('Тип имот')
                            ->options(LeadResource::getPropertyTypeOptions())
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('credit_type') === 'mortgage')
                            ->requiredIf('credit_type', 'mortgage'),
                        TextInput::make('property_location')
                            ->label('Местоположение на имота')
                            ->maxLength(120)
                            ->rule(CyrillicText::withoutLatin('Местоположението на имота'))
                            ->helperText('Не използвайте латински букви.')
                            ->visible(fn (Get $get): bool => $get('credit_type') === 'mortgage')
                            ->requiredIf('credit_type', 'mortgage'),
                    ]),
                Section::make('Поръчители')
                    ->schema([
                        Repeater::make('guarantors')
                            ->label('Поръчители')
                            ->relationship('guarantors')
                            ->defaultItems(0)
                            ->addActionLabel('Добави поръчител')
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): string => trim(($state['first_name'] ?? '').' '.($state['last_name'] ?? '')) ?: 'Нов поръчител')
                            ->grid(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('Име')
                                    ->required()
                                    ->maxLength(60)
                                    ->rule(CyrillicText::lettersOnly('Името на поръчителя'))
                                    ->helperText('Пишете само на кирилица.'),
                                TextInput::make('last_name')
                                    ->label('Фамилия')
                                    ->required()
                                    ->maxLength(60)
                                    ->rule(CyrillicText::lettersOnly('Фамилията на поръчителя'))
                                    ->helperText('Пишете само на кирилица.'),
                                TextInput::make('egn')
                                    ->label('ЕГН')
                                    ->password()
                                    ->revealable()
                                    ->autocomplete('off')
                                    ->stripCharacters([' ', '-'])
                                    ->rule('digits:10')
                                    ->minLength(10)
                                    ->maxLength(10),
                                TextInput::make('phone')
                                    ->label('Телефон')
                                    ->tel()
                                    ->maxLength(30),
                                Select::make('status')
                                    ->label('Статус')
                                    ->options(LeadResource::getGuarantorStatusOptions())
                                    ->required()
                                    ->native(false),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Документи към клиента')
                    ->schema([
                        FileUpload::make('documents')
                            ->label('Прикачени файлове')
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
                            ->maxFiles(15)
                            ->maxSize(10240)
                            ->helperText('Допустими са PDF, DOC, DOCX, XLS, XLSX, JPG, PNG и WEBP до 10 MB на файл.')
                            ->deleteUploadedFileUsing(static function (string $file): void {
                                Storage::disk('local')->delete($file);
                            })
                            ->columnSpanFull(),
                    ]),
                Section::make('Вътрешна бележка')
                    ->schema([
                        RichEditor::make('internal_notes')
                            ->label('Бележка')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                ['h2', 'h3', 'blockquote', 'bulletList', 'orderedList'],
                                ['attachFiles'],
                                ['undo', 'redo'],
                            ])
                            ->fileAttachmentsDisk('local')
                            ->fileAttachmentsVisibility('private')
                            ->fileAttachmentsDirectory(fn (Lead $record): string => "lead-notes/{$record->getKey()}")
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
