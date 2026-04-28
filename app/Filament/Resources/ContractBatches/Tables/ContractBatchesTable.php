<?php

namespace App\Filament\Resources\ContractBatches\Tables;

use App\Models\ContractBatch;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_full_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lead_id')
                    ->label('Заявка')
                    ->formatStateUsing(static fn (?int $state): string => filled($state) ? '#'.$state : 'Ръчно')
                    ->sortable(),
                TextColumn::make('company_key')
                    ->label('Фирма')
                    ->formatStateUsing(static fn (?string $state): string => ContractBatch::getCompanyLabel($state))
                    ->searchable(),
                TextColumn::make('selected_document_types')
                    ->label('Документи')
                    ->state(fn (ContractBatch $record): string => (string) count($record->selected_document_types ?? []))
                    ->tooltip(fn (ContractBatch $record): string => implode(', ', $record->getSelectedDocumentTypeLabels()))
                    ->suffix(' бр.'),
                TextColumn::make('createdBy.name')
                    ->label('Генерирани от')
                    ->placeholder('Няма'),
                TextColumn::make('generated_at')
                    ->label('Генерирани на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('downloadCombinedPdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (ContractBatch $record): bool => $record->combinedPdfExists())
                    ->url(fn (ContractBatch $record): string => route('admin.contract-batches.combined-pdf.download', $record)),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('generated_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
