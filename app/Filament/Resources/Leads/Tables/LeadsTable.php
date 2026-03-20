<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;

class LeadsTable
{
    public static function configure(Table $table, bool $isAttachedResource = false): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('credit_type')
                    ->label('Тип кредит')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (?string $state): string => LeadResource::getCreditTypeLabel($state))
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Кандидат')
                    ->state(fn (Lead $record): string => trim(implode(' ', array_filter([
                        $record->first_name,
                        $record->middle_name,
                        $record->last_name,
                    ]))))
                    ->description(fn (Lead $record): ?string => filled($record->credit_bank) ? 'Банка по кредита: '.$record->credit_bank : null)
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
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Имейл')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Град')
                    ->searchable(),
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
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->colors([
                        'warning' => 'new',
                        'primary' => 'in_progress',
                        'gray' => 'processed',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (?string $state): string => LeadResource::getStatusLabel($state))
                    ->searchable(),
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
                SelectFilter::make('credit_type')
                    ->label('Тип кредит')
                    ->options(LeadResource::getCreditTypeOptions())
                    ->native(false),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(LeadResource::getStatusOptions())
                    ->native(false),
            ])
            ->recordActions(array_values(array_filter([
                $isAttachedResource ? static::makeReturnToPrimaryAction() : null,
                ViewAction::make(),
                EditAction::make(),
            ])))
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([]);
    }

    private static function makeReturnToPrimaryAction(): Action
    {
        return Action::make('return_to_primary')
            ->label('Върни')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Връщане към основния служител')
            ->modalDescription('Заявката ще бъде махната от "Закачени към мен" и ще остане само при основния служител.')
            ->visible(function (Lead $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && $user->isAdmin()
                    && $record->additional_user_id === $user->id;
            })
            ->action(function (Lead $record): void {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                try {
                    app(LeadService::class)->returnAttachedLeadToPrimary($record, $user);
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Заявката е върната към основния служител.')
                    ->success()
                    ->send();
            });
    }
}
