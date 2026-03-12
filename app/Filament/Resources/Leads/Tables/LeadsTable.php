<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Filament\Resources\Leads\LeadResource;
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
            ->columns([
                TextColumn::make('credit_type')
                    ->label('Тип кредит')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (?string $state): string => LeadResource::getCreditTypeLabel($state))
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('Име')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Фамилия')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Имейл')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Град')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Сума')
                    ->numeric(0, locale: 'bg')
                    ->suffix(' лв.')
                    ->sortable(),
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
