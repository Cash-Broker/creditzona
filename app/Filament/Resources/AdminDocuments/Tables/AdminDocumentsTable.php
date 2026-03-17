<?php

namespace App\Filament\Resources\AdminDocuments\Tables;

use App\Models\AdminDocument;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('original_file_name')
                    ->label('Файл')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('file_extension')
                    ->label('Тип')
                    ->state(fn (AdminDocument $record): ?string => $record->getFileExtension())
                    ->badge(),
                TextColumn::make('file_size')
                    ->label('Размер')
                    ->state(fn (AdminDocument $record): ?string => $record->getReadableFileSize())
                    ->placeholder('Няма'),
                TextColumn::make('uploadedBy.name')
                    ->label('Качен от')
                    ->placeholder('Няма')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Качен на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Отвори')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (AdminDocument $record): string => route('admin.documents.open', $record))
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->label('Свали')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (AdminDocument $record): string => route('admin.documents.download', $record)),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
