<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Models\Blog;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Заглавие')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        FileUpload::make('image_path')
                            ->label('Изображение')
                            ->image()
                            ->disk('public')
                            ->directory('blogs')
                            ->visibility('public')
                            ->fetchFileInformation(false)
                            ->helperText('Файлът се качва в storage/app/public/blogs и се достъпва през /storage/...')
                            ->getUploadedFileUsing(static function (string $file): ?array {
                                $url = Blog::getPublicImageUrl($file);

                                if (blank($url)) {
                                    return null;
                                }

                                return [
                                    'name' => basename($file),
                                    'size' => 0,
                                    'type' => null,
                                    'url' => $url,
                                ];
                            })
                            ->saveUploadedFileUsing(static function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                                if (! $file->exists()) {
                                    return null;
                                }

                                $path = $file->storePubliclyAs(
                                    $component->getDirectory(),
                                    $component->getUploadedFileNameForStorage($file),
                                    $component->getDiskName(),
                                );

                                return blank($path) ? null : '/storage/' . ltrim($path, '/');
                            })
                            ->deleteUploadedFileUsing(static function (string $file): void {
                                $storagePath = Blog::getStorageImagePath($file);

                                if (blank($storagePath)) {
                                    return;
                                }

                                Storage::disk('public')->delete($storagePath);
                            }),
                        Toggle::make('is_published')
                            ->label('Публикувана')
                            ->default(true)
                            ->inline(false),
                        DateTimePicker::make('published_at')
                            ->label('Дата на публикуване')
                            ->seconds(false)
                            ->timezone('Europe/Sofia'),
                    ]),
                Section::make('Съдържание')
                    ->schema([
                        Textarea::make('excerpt')
                            ->label('Кратко описание')
                            ->rows(3)
                            ->autosize()
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->label('Съдържание')
                            ->required()
                            ->rows(12)
                            ->autosize()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
