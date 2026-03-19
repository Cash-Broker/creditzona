<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основни данни')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('credit_type')
                            ->label('Тип кредит')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn (?string $state): string => LeadResource::getCreditTypeLabel($state)),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->colors([
                                'warning' => 'new',
                                'primary' => 'in_progress',
                                'gray' => 'processed',
                                'success' => 'approved',
                                'danger' => 'rejected',
                            ])
                            ->formatStateUsing(fn (?string $state): string => LeadResource::getStatusLabel($state)),
                        TextEntry::make('amount')
                            ->label('Сума')
                            ->numeric(0, locale: 'bg')
                            ->suffix(' €'),
                        TextEntry::make('first_name')
                            ->label('Име'),
                        TextEntry::make('middle_name')
                            ->label('Презиме')
                            ->placeholder('Няма'),
                        TextEntry::make('last_name')
                            ->label('Фамилия'),
                        TextEntry::make('egn')
                            ->label('ЕГН')
                            ->formatStateUsing(fn (?string $state): string => Lead::maskEgn($state))
                            ->placeholder('Няма'),
                        TextEntry::make('phone')
                            ->label('Телефон'),
                        TextEntry::make('email')
                            ->label('Имейл')
                            ->placeholder('Няма'),
                        TextEntry::make('city')
                            ->label('Град')
                            ->placeholder('Няма'),
                    ]),
                Section::make('Екип по заявката')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('assignedUser.name')
                            ->label('Основен служител')
                            ->placeholder('Няма'),
                        TextEntry::make('additionalUser.name')
                            ->label('Допълнителен служител')
                            ->placeholder('Няма'),
                    ]),
                Section::make('Допълнителна информация')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('workplace')
                            ->label('Месторабота')
                            ->placeholder('Няма'),
                        TextEntry::make('job_title')
                            ->label('Длъжност')
                            ->placeholder('Няма'),
                        TextEntry::make('salary')
                            ->label('Заплата')
                            ->numeric(0, locale: 'bg')
                            ->suffix(' €')
                            ->placeholder('Няма'),
                        TextEntry::make('marital_status')
                            ->label('Семейно положение')
                            ->formatStateUsing(fn (?string $state): string => LeadResource::getMaritalStatusLabel($state))
                            ->placeholder('Няма'),
                        TextEntry::make('children_under_18')
                            ->label('Деца под 18')
                            ->placeholder('Няма'),
                        TextEntry::make('salary_bank')
                            ->label('Банка, в която влиза заплатата')
                            ->placeholder('Няма'),
                        TextEntry::make('credit_bank')
                            ->label('Банка по кредита')
                            ->placeholder('Няма'),
                    ]),
                Section::make('Данни за имота')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('property_type')
                            ->label('Тип имот')
                            ->formatStateUsing(fn (?string $state): string => LeadResource::getPropertyTypeLabel($state))
                            ->placeholder('Няма'),
                        TextEntry::make('property_location')
                            ->label('Местоположение на имота')
                            ->placeholder('Няма'),
                    ]),
                Section::make('Поръчители')
                    ->schema([
                        RepeatableEntry::make('guarantors')
                            ->label('Поръчители')
                            ->placeholder('Няма добавени поръчители')
                            ->contained(false)
                            ->schema([
                                Section::make('Основни данни на поръчителя')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('status')
                                            ->label('Статус')
                                            ->badge()
                                            ->colors([
                                                'success' => 'suitable',
                                                'danger' => 'unsuitable',
                                                'gray' => 'declined',
                                            ])
                                            ->formatStateUsing(fn (?string $state): string => LeadResource::getGuarantorStatusLabel($state)),
                                        TextEntry::make('amount')
                                            ->label('Сума')
                                            ->numeric(0, locale: 'bg')
                                            ->suffix(' €')
                                            ->placeholder('Няма'),
                                        TextEntry::make('first_name')
                                            ->label('Име'),
                                        TextEntry::make('middle_name')
                                            ->label('Презиме')
                                            ->placeholder('Няма'),
                                        TextEntry::make('last_name')
                                            ->label('Фамилия'),
                                        TextEntry::make('egn')
                                            ->label('ЕГН')
                                            ->formatStateUsing(fn (?string $state): string => LeadGuarantor::maskEgn($state))
                                            ->placeholder('Няма'),
                                        TextEntry::make('phone')
                                            ->label('Телефон')
                                            ->placeholder('Няма'),
                                        TextEntry::make('email')
                                            ->label('Имейл')
                                            ->placeholder('Няма'),
                                        TextEntry::make('city')
                                            ->label('Град')
                                            ->placeholder('Няма'),
                                    ]),
                                Section::make('Допълнителна информация за поръчителя')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('workplace')
                                            ->label('Месторабота')
                                            ->placeholder('Няма'),
                                        TextEntry::make('job_title')
                                            ->label('Длъжност')
                                            ->placeholder('Няма'),
                                        TextEntry::make('salary')
                                            ->label('Заплата')
                                            ->numeric(0, locale: 'bg')
                                            ->suffix(' €')
                                            ->placeholder('Няма'),
                                        TextEntry::make('marital_status')
                                            ->label('Семейно положение')
                                            ->formatStateUsing(fn (?string $state): string => LeadResource::getMaritalStatusLabel($state))
                                            ->placeholder('Няма'),
                                        TextEntry::make('children_under_18')
                                            ->label('Деца под 18')
                                            ->placeholder('Няма'),
                                        TextEntry::make('salary_bank')
                                            ->label('Банка, в която влиза заплатата')
                                            ->placeholder('Няма'),
                                        TextEntry::make('credit_bank')
                                            ->label('Банка по кредита')
                                            ->placeholder('Няма'),
                                    ]),
                                Section::make('Данни за имота на поръчителя')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('property_type')
                                            ->label('Тип имот')
                                            ->formatStateUsing(fn (?string $state): string => LeadResource::getPropertyTypeLabel($state))
                                            ->placeholder('Няма'),
                                        TextEntry::make('property_location')
                                            ->label('Местоположение на имота')
                                            ->placeholder('Няма'),
                                    ]),
                                Section::make('Документи към поръчителя')
                                    ->schema([
                                        ViewEntry::make('document_downloads')
                                            ->label('Прикачени файлове')
                                            ->view('filament.resources.leads.infolists.guarantor-document-downloads')
                                            ->state(fn (LeadGuarantor $record): array => $record->getDocumentDownloads())
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Вътрешна бележка за поръчителя')
                                    ->schema([
                                        TextEntry::make('internal_notes')
                                            ->label('Бележка')
                                            ->prose()
                                            ->placeholder('Няма')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
                Section::make('Документи към клиента')
                    ->schema([
                        ViewEntry::make('document_downloads')
                            ->label('Прикачени файлове')
                            ->view('filament.resources.leads.infolists.document-downloads')
                            ->state(fn (Lead $record): array => $record->getDocumentDownloads())
                            ->columnSpanFull(),
                    ]),
                Section::make('Съгласие за лични данни')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('privacy_consent_accepted')
                            ->label('Статус')
                            ->state(fn (Lead $record): string => $record->hasPrivacyConsent() ? 'Дадено' : 'Няма')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Дадено' ? 'success' : 'gray'),
                        TextEntry::make('privacy_consent_accepted_at')
                            ->label('Дадено на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                            ->placeholder('Няма'),
                        ViewEntry::make('privacy_consent_document_downloads')
                            ->label('Приет документ')
                            ->view('filament.resources.leads.infolists.document-downloads')
                            ->state(fn (Lead $record): array => array_map(
                                static fn (array $document): array => array_merge($document, [
                                    'description' => 'Документът, с който клиентът е дал съгласие за обработване на личните данни.',
                                ]),
                                $record->getPrivacyConsentDocumentDownloads(),
                            ))
                            ->columnSpanFull(),
                    ]),
                Section::make('Вътрешна бележка')
                    ->schema([
                        TextEntry::make('internal_notes')
                            ->label('Бележка')
                            ->prose()
                            ->placeholder('Няма')
                            ->columnSpanFull(),
                    ]),
                Section::make('Системна информация')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Създадена на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('updated_at')
                            ->label('Променена на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                    ]),
            ]);
    }
}
