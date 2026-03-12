<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Съобщение')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Име и фамилия'),
                        TextEntry::make('phone')
                            ->label('Телефон'),
                        TextEntry::make('email')
                            ->label('Имейл'),
                        TextEntry::make('created_at')
                            ->label('Получено на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('message')
                            ->label('Съобщение')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
