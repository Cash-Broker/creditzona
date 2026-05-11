<?php

namespace App\Filament\Resources\ApprovedReturnedLeadArchives;

use App\Filament\Resources\ApprovedReturnedLeadArchives\Pages\EditApprovedReturnedLeadArchive;
use App\Filament\Resources\ApprovedReturnedLeadArchives\Pages\ListApprovedReturnedLeadArchives;
use App\Filament\Resources\ApprovedReturnedLeadArchives\Pages\ViewApprovedReturnedLeadArchive;
use App\Filament\Resources\Leads\LeadResource as BaseLeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
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

class ApprovedReturnedLeadArchiveResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Архивирани одобрени върнати';

    protected static ?int $navigationSort = 16;

    protected static ?string $modelLabel = 'архивирана одобрена върната заявка';

    protected static ?string $pluralModelLabel = 'архивирани одобрени върнати заявки';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return BaseLeadResource::getRecordTitle($record);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->isAdmin() || $user->isOperator());
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $record instanceof Lead) {
            return false;
        }

        if ($record->approved_returned_archived_at === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOperator()
            && $record->approved_returned_archived_user_id === $user->id;
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
        return LeadsTable::configure(
            $table,
            static::class,
            showReturnedMeta: true,
            showApprovedReturnedArchiveMeta: true,
            defaultSortColumn: 'approved_returned_archived_at',
        );
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
            'index' => ListApprovedReturnedLeadArchives::route('/'),
            'view' => ViewApprovedReturnedLeadArchive::route('/{record}'),
            'edit' => EditApprovedReturnedLeadArchive::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query->approvedReturnedArchive();
        }

        return $query->approvedReturnedArchiveForUser($user);
    }
}
