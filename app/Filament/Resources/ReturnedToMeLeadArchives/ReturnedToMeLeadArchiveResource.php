<?php

namespace App\Filament\Resources\ReturnedToMeLeadArchives;

use App\Filament\Resources\Leads\LeadResource as BaseLeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Filament\Resources\ReturnedToMeLeadArchives\Pages\EditReturnedToMeLeadArchive;
use App\Filament\Resources\ReturnedToMeLeadArchives\Pages\ListReturnedToMeLeadArchives;
use App\Filament\Resources\ReturnedToMeLeadArchives\Pages\ViewReturnedToMeLeadArchive;
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

class ReturnedToMeLeadArchiveResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Архивирани върнати към мен';

    protected static ?int $navigationSort = 14;

    protected static ?string $modelLabel = 'архивирана върната към мен заявка';

    protected static ?string $pluralModelLabel = 'архивирани върнати към мен заявки';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return BaseLeadResource::getRecordTitle($record);
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
            && $record instanceof Lead
            && $record->returned_to_primary_archived_user_id === $user->id
            && $record->returned_to_primary_archived_at !== null
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
        return LeadsTable::configure($table, static::class, showReturnedMeta: true, showReturnedToMeArchiveMeta: true);
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
            'index' => ListReturnedToMeLeadArchives::route('/'),
            'view' => ViewReturnedToMeLeadArchive::route('/{record}'),
            'edit' => EditReturnedToMeLeadArchive::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->returnedToPrimaryArchiveForUser($user);
    }
}
