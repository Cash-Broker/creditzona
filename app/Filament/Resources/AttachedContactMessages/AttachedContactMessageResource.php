<?php

namespace App\Filament\Resources\AttachedContactMessages;

use App\Filament\Resources\AttachedContactMessages\Pages\ListAttachedContactMessages;
use App\Filament\Resources\AttachedContactMessages\Pages\ViewAttachedContactMessage;
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

class AttachedContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Съобщения към мен';

    protected static ?int $navigationSort = 14;

    protected static ?string $modelLabel = 'съобщение към мен';

    protected static ?string $pluralModelLabel = 'съобщения към мен';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isOperator()) {
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
        return ContactMessagesTable::configure($table, isAttachedResource: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttachedContactMessages::route('/'),
            'view' => ViewAttachedContactMessage::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isOperator();
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->isOperator()
            && $record instanceof ContactMessage
            && $record->assigned_user_id === $user->id;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isOperator()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->attachedToUser($user);
    }
}
