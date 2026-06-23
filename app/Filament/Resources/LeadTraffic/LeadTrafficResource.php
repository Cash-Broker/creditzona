<?php

namespace App\Filament\Resources\LeadTraffic;

use App\Filament\Resources\LeadTraffic\Pages\ListLeadTraffic;
use App\Filament\Resources\LeadTraffic\Tables\LeadTrafficTable;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LeadTrafficResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|UnitEnum|null $navigationGroup = 'Трафик';

    protected static ?string $navigationLabel = 'IP и устройства';

    protected static ?int $navigationSort = 99;

    protected static ?string $modelLabel = 'заявка';

    protected static ?string $pluralModelLabel = 'заявки';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canViewLeadTraffic();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return LeadTrafficTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        // Only submissions that actually carry traffic data (an IP was captured)
        // — i.e. the new leads since this feature shipped, never the historical
        // ones that predate IP/User-Agent tracking. Newest first.
        return parent::getEloquentQuery()
            ->whereNotNull('ip_address')
            ->latest('created_at')
            ->latest('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadTraffic::route('/'),
        ];
    }
}
