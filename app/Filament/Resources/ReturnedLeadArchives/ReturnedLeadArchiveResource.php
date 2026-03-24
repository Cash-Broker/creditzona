<?php

namespace App\Filament\Resources\ReturnedLeadArchives;

use App\Filament\Resources\Leads\LeadResource as BaseLeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Filament\Resources\ReturnedLeadArchives\Pages\EditReturnedLeadArchive;
use App\Filament\Resources\ReturnedLeadArchives\Pages\ListReturnedLeadArchives;
use App\Filament\Resources\ReturnedLeadArchives\Pages\ViewReturnedLeadArchive;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReturnedLeadArchiveResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Архивирани върнати';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'архивирана върната заявка';

    protected static ?string $pluralModelLabel = 'архивирани върнати заявки';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return BaseLeadResource::getRecordTitle($record);
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
            && $record instanceof Lead
            && $record->returned_additional_user_id === $user->id
            && $record->additional_user_id === null;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        return (string) static::getEloquentQuery()->count();
    }

    public static function form(Schema $schema): Schema
    {
        return LeadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table, static::class, showReturnedMeta: true);
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
            'index' => ListReturnedLeadArchives::route('/'),
            'view' => ViewReturnedLeadArchive::route('/{record}'),
            'edit' => EditReturnedLeadArchive::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->returnedArchiveForUser($user);
    }
}
