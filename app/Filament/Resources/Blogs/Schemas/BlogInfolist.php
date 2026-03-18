<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Models\Blog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Заглавие')
                            ->columnSpanFull(),
                        TextEntry::make('slug')
                            ->label('Slug'),
                        IconEntry::make('is_published')
                            ->label('Публикувана')
                            ->boolean(),
                        TextEntry::make('published_at')
                            ->label('Дата на публикуване')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                            ->placeholder('Няма'),
                        ImageEntry::make('image_path')
                            ->label('Изображение')
                            ->state(fn (Blog $record): ?string => Blog::getPublicImageUrl($record->image_path))
                            ->checkFileExistence(false)
                            ->placeholder('Няма')
                            ->columnSpanFull(),
                    ]),
                Section::make('Съдържание')
                    ->schema([
                        TextEntry::make('excerpt')
                            ->label('Кратко описание')
                            ->markdown()
                            ->placeholder('Няма')
                            ->columnSpanFull(),
                        TextEntry::make('content')
                            ->label('Съдържание')
                            ->markdown()
                            ->columnSpanFull(),
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
