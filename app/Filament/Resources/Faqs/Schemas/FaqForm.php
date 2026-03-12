<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('question')
                            ->label('Въпрос')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Ред на подреждане')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_published')
                            ->label('Публикуван')
                            ->default(true)
                            ->inline(false),
                    ]),
                Section::make('Отговор')
                    ->schema([
                        Textarea::make('answer')
                            ->label('Отговор')
                            ->required()
                            ->rows(8)
                            ->autosize()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
