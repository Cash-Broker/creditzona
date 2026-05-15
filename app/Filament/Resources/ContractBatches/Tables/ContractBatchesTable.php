<?php

namespace App\Filament\Resources\ContractBatches\Tables;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Models\ContractBatch;
use App\Models\User;
use App\Services\Contracts\ContractBatchService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
                TextColumn::make('commission_eur')
                    ->label('Комисионна')
                    ->alignEnd()
                    ->placeholder('')
                    ->state(function (ContractBatch $record): ?string {
                        $commission = data_get($record->getSubmittedInput(), 'financial.commission_eur')
                            ?? data_get($record->getSubmittedInput(), 'financial.fee_eur');

                        if ($commission === null) {
                            return null;
                        }

                        return number_format((float) $commission, 0, '.', ' ').' €';
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
                Action::make('downloadCombinedDocx')
                    ->label('')
                    ->tooltip(fn (ContractBatch $record): string => $record->combinedDocxExists()
                        ? 'Свали Word'
                        : 'Word не е генериран — отворете "Редактирай" за да продължите към документите.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('info')
                    ->extraAttributes(['class' => 'cz-row-action cz-row-action-download'])
                    ->disabled(fn (ContractBatch $record): bool => ! $record->combinedDocxExists())
                    ->url(fn (ContractBatch $record): ?string => $record->combinedDocxExists()
                        ? route('admin.contract-batches.combined-docx.download', $record)
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
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw('COALESCE('.static::protocolDateOrderExpression().', DATE(created_at)) DESC')
                ->orderByDesc('id'))
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(5)
            ->striped()
            ->toolbarActions([
                static::makeAttachBulkAction(),
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

    public static function makeAttachBulkAction(): BulkAction
    {
        return BulkAction::make('attach_selected')
            ->label('Прикачи към оператор')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('primary')
            ->visible(static fn (): bool => auth()->user()?->canViewAllContracts() ?? false)
            ->modalHeading('Прикачи избраните договори към оператор')
            ->modalDescription('Изберете потребител, който ще има достъп до избраните пакети. Изпразнете полето, за да премахнете прикачването от избраните.')
            ->modalSubmitActionLabel('Прикачи')
            ->modalCancelActionLabel('Отказ')
            ->deselectRecordsAfterCompletion()
            ->schema([
                Select::make('operator_id')
                    ->label('Потребител')
                    ->placeholder('— без прикачване —')
                    ->options(static fn (): array => User::query()
                        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->nullable()
                    ->searchable(),
            ])
            ->action(function (array $data, Collection $records): void {
                $actor = auth()->user();

                if (! $actor instanceof User) {
                    return;
                }

                $operator = filled($data['operator_id'] ?? null)
                    ? User::find($data['operator_id'])
                    : null;

                $service = app(ContractBatchService::class);
                $succeeded = 0;
                $failed = 0;

                foreach ($records as $record) {
                    if (! $record instanceof ContractBatch) {
                        $failed++;

                        continue;
                    }

                    try {
                        $service->attachToOperator($record, $operator, $actor);
                        $succeeded++;
                    } catch (AuthorizationException|DomainException) {
                        $failed++;
                    }
                }

                if ($succeeded === 0) {
                    Notification::make()
                        ->title('Нито един договор не беше прикачен.')
                        ->warning()
                        ->send();

                    return;
                }

                $title = $operator !== null
                    ? sprintf('Прикачени са %d договора към %s.', $succeeded, $operator->name)
                    : sprintf('Премахнато е прикачването от %d договора.', $succeeded);

                if ($failed > 0) {
                    $title .= sprintf(' (%d пропуснати)', $failed);
                }

                Notification::make()
                    ->title($title)
                    ->success()
                    ->send();
            });
    }

    private static function protocolDateOrderExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql' => "JSON_UNQUOTE(JSON_EXTRACT(input_payload, '$.dates.consultation_protocol_date'))",
            default => "json_extract(input_payload, '$.dates.consultation_protocol_date')",
        };
    }
}
