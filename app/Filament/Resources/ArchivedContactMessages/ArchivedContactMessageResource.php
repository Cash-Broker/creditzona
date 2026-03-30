<?php

namespace App\Filament\Resources\ArchivedContactMessages;

use App\Filament\Resources\ArchivedContactMessages\Pages\ListArchivedContactMessages;
use App\Filament\Resources\ArchivedContactMessages\Pages\ViewArchivedContactMessage;
use App\Filament\Resources\ContactMessages\Schemas\ContactMessageInfolist;
use App\Filament\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ArchivedContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Архив на съобщения';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'архивирано контактно съобщение';

    protected static ?string $pluralModelLabel = 'архив на съобщения';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return null;
        }

        return (string) static::getEloquentQuery()->count();
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table, isArchiveResource: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArchivedContactMessages::route('/'),
            'view' => ViewArchivedContactMessage::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->isAdmin()
            && $record instanceof ContactMessage
            && $record->admin_archived_at !== null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->adminArchived()->with(['assignedUser', 'adminArchivedByUser']);
    }
}
