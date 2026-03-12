<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основни данни')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('credit_type')
                            ->label('Тип кредит')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn (?string $state): string => LeadResource::getCreditTypeLabel($state)),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->colors([
                                'warning' => 'new',
                                'primary' => 'in_progress',
                                'success' => 'processed',
                            ])
                            ->formatStateUsing(fn (?string $state): string => LeadResource::getStatusLabel($state)),
                        TextEntry::make('first_name')
                            ->label('Име'),
                        TextEntry::make('last_name')
                            ->label('Фамилия'),
                        TextEntry::make('phone')
                            ->label('Телефон'),
                        TextEntry::make('email')
                            ->label('Имейл'),
                        TextEntry::make('city')
                            ->label('Град'),
                        TextEntry::make('amount')
                            ->label('Сума')
                            ->numeric(0, locale: 'bg')
                            ->suffix(' лв.'),
                    ]),
                Section::make('Данни за имота')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('property_type')
                            ->label('Тип имот')
                            ->formatStateUsing(fn (?string $state): string => LeadResource::getPropertyTypeLabel($state))
                            ->placeholder('Няма'),
                        TextEntry::make('property_location')
                            ->label('Местоположение на имота')
                            ->placeholder('Няма'),
                    ]),
                Section::make('Системна информация')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Създадена на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('updated_at')
                            ->label('Променена на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                    ]),
            ]);
    }
}
