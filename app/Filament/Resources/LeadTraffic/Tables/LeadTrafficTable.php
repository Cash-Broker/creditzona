<?php

namespace App\Filament\Resources\LeadTraffic\Tables;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadTrafficTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Кандидат')
                    ->state(fn (Lead $record): string => trim(implode(' ', array_filter([
                        $record->first_name,
                        $record->middle_name,
                        $record->last_name,
                    ]))))
                    ->searchable(['first_name', 'middle_name', 'last_name']),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('credit_type')
                    ->label('Тип кредит')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => LeadResource::getCreditTypeLabel($state))
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (?string $state): string|array => LeadResource::getStatusBadgeColor($state))
                    ->formatStateUsing(fn (?string $state): string => LeadResource::getStatusLabel($state))
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP адрес')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('IP адресът е копиран')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_agent')
                    ->label('Браузър / устройство (User-Agent)')
                    ->placeholder('—')
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn (Lead $record): ?string => $record->user_agent)
                    ->searchable()
                    ->width('28rem'),

                TextColumn::make('city')
                    ->label('Град')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('utm_source')
                    ->label('UTM source')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('utm_campaign')
                    ->label('UTM campaign')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('utm_medium')
                    ->label('UTM medium')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gclid')
                    ->label('gclid')
                    ->placeholder('—')
                    ->limit(24)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(LeadResource::getStatusOptions())
                    ->native(false),
            ])
            ->recordUrl(null)
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'));
    }
}
