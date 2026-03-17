<?php

namespace App\Filament\Resources\AdminDocuments\Schemas;

use App\Models\AdminDocument;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Заглавие'),
                        TextEntry::make('original_file_name')
                            ->label('Оригинално име')
                            ->placeholder('Няма'),
                        TextEntry::make('file_extension')
                            ->label('Тип файл')
                            ->state(fn (AdminDocument $record): ?string => $record->getFileExtension())
                            ->badge()
                            ->placeholder('Няма'),
                        TextEntry::make('file_size')
                            ->label('Размер')
                            ->state(fn (AdminDocument $record): ?string => $record->getReadableFileSize())
                            ->placeholder('Няма'),
                        TextEntry::make('mime_type')
                            ->label('MIME тип')
                            ->placeholder('Няма'),
                        TextEntry::make('uploadedBy.name')
                            ->label('Качен от')
                            ->placeholder('Няма'),
                        TextEntry::make('created_at')
                            ->label('Качен на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('updated_at')
                            ->label('Променен на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('description')
                            ->label('Описание')
                            ->placeholder('Няма')
                            ->columnSpanFull(),
                    ]),
                Section::make('Достъп до файла')
                    ->schema([
                        ViewEntry::make('file_actions')
                            ->label('Файл')
                            ->view('filament.resources.admin-documents.infolists.file-actions')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
