<?php

namespace App\Filament\Resources\ContractBatches\Tables;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Models\ContractBatch;
use App\Services\Contracts\CurrencyFormatterService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('client_full_name')
                    ->label('Клиент')
                    ->description(fn (ContractBatch $record): string => 'с ЕГН '.ContractBatch::maskEgn(data_get($record->getSubmittedInput(), 'client.egn')))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('co_applicant_full_name')
                    ->label('Поръчител')
                    ->description(fn (ContractBatch $record): string => filled($record->co_applicant_full_name)
                        ? 'с ЕГН '.ContractBatch::maskEgn(data_get($record->getSubmittedInput(), 'co_applicant.egn'))
                        : '')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('company_key')
                    ->label('Фирма')
                    ->formatStateUsing(static fn (?string $state): string => ContractBatch::getCompanyLabel($state))
                    ->searchable(),
                TextColumn::make('client_city')
                    ->label('Град')
                    ->formatStateUsing(static fn (?string $state): string => filled($state) ? 'гр. '.$state : '—')
                    ->searchable(),
                TextColumn::make('commission_bgn')
                    ->label('Комисионна')
                    ->alignEnd()
                    ->placeholder('')
                    ->state(function (ContractBatch $record): ?string {
                        $commission = data_get($record->getSubmittedInput(), 'financial.commission_eur')
                            ?? data_get($record->getSubmittedInput(), 'financial.fee_eur');

                        if ($commission === null) {
                            return null;
                        }

                        $bgn = ((float) $commission) * 1.95583;

                        return number_format($bgn, 0, '.', ' ').' лв.';
                    }),
            ])
            ->searchable()
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('downloadCombinedPdf')
                    ->label('')
                    ->tooltip(fn (ContractBatch $record): string => $record->combinedPdfExists()
                        ? 'Свали PDF'
                        : 'PDF не е генериран — отворете "Редактирай" за да продължите към документите.')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('info')
                    ->extraAttributes(['class' => 'cz-row-action cz-row-action-download'])
                    ->disabled(fn (ContractBatch $record): bool => ! $record->combinedPdfExists())
                    ->url(fn (ContractBatch $record): ?string => $record->combinedPdfExists()
                        ? route('admin.contract-batches.combined-pdf.download', $record)
                        : null)
                    ->openUrlInNewTab(),
                ContractBatchResource::makeAttachAction()
                    ->label('')
                    ->tooltip('Прикачи към оператор')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->color('primary')
                    ->extraAttributes(['class' => 'cz-row-action cz-row-action-attach']),
                EditAction::make()
                    ->label('')
                    ->tooltip('Редактирай')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('warning')
                    ->extraAttributes(['class' => 'cz-row-action cz-row-action-edit'])
                    ->url(fn (ContractBatch $record): string => ContractBatchResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Изтрий')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->extraAttributes(['class' => 'cz-row-action cz-row-action-delete'])
                    ->modalHeading('Изтриване на договорен пакет')
                    ->modalDescription('Сигурни ли сте? Договорният пакет и всички генерирани файлове към него ще бъдат изтрити безвъзвратно.')
                    ->modalSubmitActionLabel('Изтрий пакета'),
            ])
            ->defaultSort('request_date', 'desc')
            ->striped()
            ->toolbarActions([
                BulkActionGroup::make([
                    static::makeDeleteBulkAction(),
                ]),
            ]);
    }

    public static function makeDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->requiresConfirmation()
            ->modalHeading('Изтриване на избраните договорни пакети')
            ->modalDescription('Сигурни ли сте? Всички избрани договорни пакети и генерираните им файлове ще бъдат изтрити безвъзвратно.')
            ->modalSubmitActionLabel('Изтрий пакетите');
    }
}
