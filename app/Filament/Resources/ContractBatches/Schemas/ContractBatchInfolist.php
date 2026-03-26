<?php

namespace App\Filament\Resources\ContractBatches\Schemas;

use App\Models\ContractBatch;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContractBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company_key')
                            ->label('Фирма')
                            ->formatStateUsing(static fn (?string $state): string => ContractBatch::getCompanyLabel($state)),
                        TextEntry::make('client_full_name')
                            ->label('Клиент'),
                        TextEntry::make('co_applicant_full_name')
                            ->label('Съкредитоискател')
                            ->placeholder('Няма'),
                        TextEntry::make('lead_id')
                            ->label('Заявка')
                            ->formatStateUsing(static fn (?int $state): string => filled($state) ? '#'.$state : 'Ръчно'),
                        TextEntry::make('request_date')
                            ->label('Дата на заявка')
                            ->date('d.m.Y'),
                        TextEntry::make('selected_document_types')
                            ->label('Документи')
                            ->state(fn (ContractBatch $record): string => implode(', ', $record->getSelectedDocumentTypeLabels()))
                            ->columnSpanFull(),
                        TextEntry::make('generated_at')
                            ->label('Генерирани на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('createdBy.name')
                            ->label('Генерирани от')
                            ->placeholder('Няма'),
                    ]),
                Section::make('Клиент')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('client_egn')
                            ->label('ЕГН')
                            ->state(fn (ContractBatch $record): string => ContractBatch::maskEgn(data_get($record->getSubmittedInput(), 'client.egn'))),
                        TextEntry::make('client_id_card_number')
                            ->label('Лична карта №')
                            ->state(fn (ContractBatch $record): string => ContractBatch::maskDocumentNumber(data_get($record->getSubmittedInput(), 'client.id_card_number'))),
                        TextEntry::make('client.id_card_issued_at')
                            ->label('Издадена на')
                            ->state(fn (ContractBatch $record): ?string => data_get($record->getSubmittedInput(), 'client.id_card_issued_at'))
                            ->date('d.m.Y')
                            ->placeholder('Няма'),
                        TextEntry::make('client.id_card_issued_by')
                            ->label('Издадена от')
                            ->state(fn (ContractBatch $record): ?string => data_get($record->getSubmittedInput(), 'client.id_card_issued_by'))
                            ->placeholder('Няма'),
                        TextEntry::make('client.permanent_address')
                            ->label('Постоянен адрес')
                            ->state(fn (ContractBatch $record): ?string => data_get($record->getSubmittedInput(), 'client.permanent_address'))
                            ->placeholder('Няма')
                            ->columnSpanFull(),
                        TextEntry::make('client.email')
                            ->label('Имейл')
                            ->state(fn (ContractBatch $record): ?string => data_get($record->getSubmittedInput(), 'client.email'))
                            ->placeholder('Няма')
                            ->columnSpanFull(),
                    ]),
                Section::make('Финансови данни')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('active_credit_count')
                            ->label('Брой активни кредити')
                            ->state(fn (ContractBatch $record): mixed => data_get($record->getSubmittedInput(), 'financial.active_credit_count'))
                            ->placeholder('Няма'),
                        TextEntry::make('fee_eur')
                            ->label('Възнаграждение')
                            ->state(fn (ContractBatch $record): ?string => data_get($record->getDerivedInput(), 'financial.fee.eur.formatted'))
                            ->placeholder('Няма'),
                        TextEntry::make('fee_bgn')
                            ->label('Възнаграждение в лева')
                            ->state(fn (ContractBatch $record): ?string => data_get($record->getDerivedInput(), 'financial.fee.bgn.formatted'))
                            ->placeholder('Няма'),
                        TextEntry::make('loan_due_date')
                            ->label('Падеж по заема')
                            ->state(fn (ContractBatch $record): ?string => data_get($record->getDerivedInput(), 'dates.loan_due_date'))
                            ->date('d.m.Y')
                            ->placeholder('Няма'),
                    ]),
                Section::make('Файлове')
                    ->schema([
                        ViewEntry::make('file_actions')
                            ->label('Документи')
                            ->view('filament.resources.contract-batches.infolists.file-actions')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
