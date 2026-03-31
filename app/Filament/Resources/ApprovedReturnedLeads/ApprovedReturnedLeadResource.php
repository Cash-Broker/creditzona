<?php

namespace App\Filament\Resources\ApprovedReturnedLeads;

use App\Filament\Resources\ApprovedReturnedLeads\Pages\EditApprovedReturnedLead;
use App\Filament\Resources\ApprovedReturnedLeads\Pages\ListApprovedReturnedLeads;
use App\Filament\Resources\ApprovedReturnedLeads\Pages\ViewApprovedReturnedLead;
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

class ApprovedReturnedLeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Одобрени върнати';

    protected static ?int $navigationSort = 14;

    protected static ?string $modelLabel = 'одобрена върната заявка';

    protected static ?string $pluralModelLabel = 'одобрени върнати заявки';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return BaseLeadResource::getRecordTitle($record);
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
            defaultSortColumn: 'approved_returned_at',
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
            'index' => ListApprovedReturnedLeads::route('/'),
            'view' => ViewApprovedReturnedLead::route('/{record}'),
            'edit' => EditApprovedReturnedLead::route('/{record}/edit'),
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
            return $query->approvedReturned();
        }

        return $query->approvedReturnedForUser($user);
    }
}
