<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\ReturnedToMeLeads\ReturnedToMeLeadResource;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use App\Support\Notes\NoteHistory;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class LeadsTable
{
    /**
     * @param  class-string  $resourceClass
     */
    public static function configure(
        Table $table,
        string $resourceClass = LeadResource::class,
        bool $isAttachedResource = false,
        bool $isReturnedToMeResource = false,
        bool $showReturnedMeta = false,
        bool $showAttachedArchiveMeta = false,
        bool $showReturnedToMeArchiveMeta = false,
        ?string $defaultSortColumn = null,
    ): Table {
        $defaultSortColumn ??= 'created_at';

        return $table
            ->poll('5s')
            ->recordClasses(fn (Lead $record): array => $record->isMarkedForLater() ? ['lead-record-later'] : [])
            ->columns([
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (?string $state): string|array => LeadResource::getStatusBadgeColor($state))
                    ->formatStateUsing(fn (?string $state): string => LeadResource::getStatusLabel($state))
                    ->searchable(),

                CheckboxColumn::make('marked_for_later')
                    ->label('Отложи')
                    ->state(fn (Lead $record): bool => $record->isMarkedForLater())
                    ->updateStateUsing(function (bool $state, Lead $record): bool {
                        app(LeadService::class)->setMarkedForLater($record, $state);

                        Notification::make()
                            ->title($state
                                ? 'Заявката е маркирана за по-късно.'
                                : 'Маркирането за по-късно е премахнато.')
                            ->success()
                            ->send();

                        return $state;
                    }),

                TextColumn::make('full_name')
                    ->label('Кандидат')
                    ->state(fn (Lead $record): string => trim(implode(' ', array_filter([
                        $record->first_name,
                        $record->middle_name,
                        $record->last_name,
                    ]))))
                    ->description(function (Lead $record): ?string {
                        $parts = [];

                        if ($record->isMarkedForLater()) {
                            $parts[] = 'Маркирана за по-късно';
                        }

                        if (filled($record->credit_bank)) {
                            $parts[] = 'Банка по кредита: '.$record->credit_bank;
                        }

                        return empty($parts) ? null : implode(' • ', $parts);
                    })
                    ->weight(fn (Lead $record): ?FontWeight => $record->isMarkedForLater() ? FontWeight::SemiBold : null)
                    ->icon(fn (Lead $record): ?string => $record->isMarkedForLater() ? 'heroicon-m-clock' : null)
                    ->iconColor('warning')
                    ->searchable(['first_name', 'middle_name', 'last_name', 'credit_bank']),

                TextColumn::make('assignedUser.name')
                    ->label('Основен служител')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('additionalUser.name')
                    ->label('Допълнителен служител')
                    ->sortable()
                    ->placeholder('Няма')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('returnedAdditionalUser.name')
                    ->label('Върната от')
                    ->sortable()
                    ->placeholder('Няма')
                    ->visible($showReturnedMeta),

                TextColumn::make('returned_to_primary_at')
                    ->label('Върната на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->visible($showReturnedMeta),

                TextColumn::make('attached_archived_at')
                    ->label('Архивирана на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->visible($showAttachedArchiveMeta),

                TextColumn::make('returned_to_primary_archived_at')
                    ->label('Архивирана на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->visible($showReturnedToMeArchiveMeta),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                TextColumn::make('latest_internal_note')
                    ->label('Последно съобщение')
                    ->state(fn (Lead $record): ?string => NoteHistory::latestPreview($record->internal_notes, 110))
                    ->description(function (Lead $record): ?string {
                        $entry = NoteHistory::latestEntry($record->internal_notes);

                        if ($entry === null) {
                            return null;
                        }

                        return implode(' • ', array_values(array_filter([
                            $entry['author'],
                            $entry['timestamp'],
                        ])));
                    })
                    ->tooltip(function (Lead $record): ?string {
                        $entry = NoteHistory::latestEntry($record->internal_notes);

                        return $entry['body'] ?? null;
                    })
                    ->placeholder('Няма')
                    ->searchable(['internal_notes'])
                    ->lineClamp(2)
                    ->width('24rem'),

                TextColumn::make('workplace')
                    ->label('Месторабота')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->label('Сума')
                    ->numeric(0, locale: 'bg')
                    ->suffix(' €')
                    ->sortable(),

                TextColumn::make('salary')
                    ->label('Заплата')
                    ->numeric(0, locale: 'bg')
                    ->suffix(' €')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('marital_status')
                    ->label('Семейно положение')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => LeadResource::getMaritalStatusLabel($state))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('guarantors_count')
                    ->label('Поръчители')
                    ->counts('guarantors')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Създадена на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Променена на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('assigned_user_id')
                    ->label('Служител')
                    ->options(fn (): array => User::query()
                        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'])
                        ? $query->where(fn (Builder $q): Builder => $q
                            ->where('assigned_user_id', $data['value'])
                            ->orWhere('additional_user_id', $data['value']))
                        : $query)
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(LeadResource::getStatusOptions())
                    ->native(false),
            ])
            ->recordActions(array_values(array_filter([
                $isAttachedResource ? AttachedLeadResource::makeReturnToPrimaryAction() : null,
                $isReturnedToMeResource ? ReturnedToMeLeadResource::makeArchiveAction() : null,
                ViewAction::make(),
                EditAction::make(),
            ])))
            ->recordUrl(
                fn (Lead $record): string => $resourceClass::getUrl('view', ['record' => $record]),
                shouldOpenInNewTab: true,
            )
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByDesc($defaultSortColumn)
                ->orderByDesc('id'))
            ->toolbarActions([
                BulkAction::make('mark_selected_for_later')
                    ->label('Маркирай за по-късно')
                    ->icon('heroicon-m-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $leadService = app(LeadService::class);

                        $records->each(fn (Lead $lead) => $leadService->setMarkedForLater($lead, true));

                        Notification::make()
                            ->title('Избраните заявки са маркирани за по-късно.')
                            ->success()
                            ->send();
                    }),

                BulkAction::make('unmark_selected_for_later')
                    ->label('Махни от по-късно')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $leadService = app(LeadService::class);

                        $records->each(fn (Lead $lead) => $leadService->setMarkedForLater($lead, false));

                        Notification::make()
                            ->title('Избраните заявки са върнати от по-късно.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
