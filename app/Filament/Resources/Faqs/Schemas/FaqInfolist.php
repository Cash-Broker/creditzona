<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('question')
                            ->label('Въпрос')
                            ->columnSpanFull(),
                        TextEntry::make('sort_order')
                            ->label('Ред на подреждане')
                            ->numeric(0, locale: 'bg'),
                        IconEntry::make('is_published')
                            ->label('Публикуван')
                            ->boolean(),
                    ]),
                Section::make('Отговор')
                    ->schema([
                        TextEntry::make('answer')
                            ->label('Отговор')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
                Section::make('Системна информация')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Създаден на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('updated_at')
                            ->label('Променен на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                    ]),
            ]);
    }
}
