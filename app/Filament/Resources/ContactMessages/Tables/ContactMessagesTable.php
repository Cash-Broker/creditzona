<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table, bool $isAttachedResource = false): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Име и фамилия')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Имейл')
                    ->searchable(),
                TextColumn::make('assignedUser.name')
                    ->label('Оператор')
                    ->placeholder('Няма')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('message')
                    ->label('Съобщение')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Получено на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions(array_values(array_filter([
                $isAttachedResource ? null : ContactMessageResource::makeAssignAction(),
                ViewAction::make(),
            ])))
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([]);
    }
}
