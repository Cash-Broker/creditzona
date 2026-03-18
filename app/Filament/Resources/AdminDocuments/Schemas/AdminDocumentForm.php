<?php

namespace App\Filament\Resources\AdminDocuments\Schemas;

use App\Models\AdminDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class AdminDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Файл')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Заглавие')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(4)
                            ->autosize()
                            ->columnSpanFull(),
                        FileUpload::make('file_path')
                            ->label('Качен файл')
                            ->required()
                            ->disk('local')
                            ->visibility('private')
                            ->directory('admin-documents')
                            ->storeFileNamesIn('original_file_name')
                            ->acceptedFileTypes(AdminDocument::getSafeUploadMimeTypes())
                            ->rules([
                                'mimetypes:'.implode(',', AdminDocument::getSafeUploadMimeTypes()),
                            ])
                            ->maxSize(51200)
                            ->helperText('Допустими са PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF и WEBP до 50 MB. Файлът се пази във вътрешното защитено хранилище.')
                            ->deleteUploadedFileUsing(static function (string $file): void {
                                Storage::disk('local')->delete($file);
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
