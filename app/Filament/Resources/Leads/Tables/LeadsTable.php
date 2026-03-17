<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
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
                    ->suffix(' лв.')
                    ->sortable(),
                TextColumn::make('salary')
                    ->label('Заплата')
                    ->numeric(0, locale: 'bg')
                    ->suffix(' лв.')
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
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([]);
    }
}
