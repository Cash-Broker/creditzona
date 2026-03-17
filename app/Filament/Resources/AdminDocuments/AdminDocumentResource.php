<?php

namespace App\Filament\Resources\AdminDocuments;

use App\Filament\Resources\AdminDocuments\Pages\CreateAdminDocument;
use App\Filament\Resources\AdminDocuments\Pages\EditAdminDocument;
use App\Filament\Resources\AdminDocuments\Pages\ListAdminDocuments;
use App\Filament\Resources\AdminDocuments\Pages\ViewAdminDocument;
use App\Filament\Resources\AdminDocuments\Schemas\AdminDocumentForm;
use App\Filament\Resources\AdminDocuments\Schemas\AdminDocumentInfolist;
use App\Filament\Resources\AdminDocuments\Tables\AdminDocumentsTable;
use App\Models\AdminDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AdminDocumentResource extends Resource
{
    protected static ?string $model = AdminDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Файлово хранилище';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'файл';

    protected static ?string $pluralModelLabel = 'файлове';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if (! $record instanceof AdminDocument) {
            return parent::getRecordTitle($record);
        }

        return $record->getDisplayTitle();
    }

    public static function form(Schema $schema): Schema
    {
        return AdminDocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AdminDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminDocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminDocuments::route('/'),
            'create' => CreateAdminDocument::route('/create'),
            'view' => ViewAdminDocument::route('/{record}'),
            'edit' => EditAdminDocument::route('/{record}/edit'),
        ];
    }
}
