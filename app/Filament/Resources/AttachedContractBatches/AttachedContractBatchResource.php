<?php

namespace App\Filament\Resources\AttachedContractBatches;

use App\Filament\Resources\AttachedContractBatches\Pages\ListAttachedContractBatches;
use App\Filament\Resources\AttachedContractBatches\Pages\ViewAttachedContractBatch;
use App\Filament\Resources\AttachedContractBatches\Tables\AttachedContractBatchesTable;
use App\Filament\Resources\ContractBatches\Schemas\ContractBatchInfolist;
use App\Models\ContractBatch;
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

class AttachedContractBatchResource extends Resource
{
    protected static ?string $model = ContractBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Документи';

    protected static ?string $navigationLabel = 'Прикачени към мен';

    protected static ?int $navigationSort = 22;

    protected static ?string $modelLabel = 'прикачен договорен пакет';

    protected static ?string $pluralModelLabel = 'прикачени договорни пакети';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'client_full_name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if (! $record instanceof ContractBatch) {
            return parent::getRecordTitle($record);
        }

        return $record->getDisplayTitle();
    }

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
        return ContractBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttachedContractBatchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttachedContractBatches::route('/'),
            'view' => ViewAttachedContractBatch::route('/{record}'),
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
            && $record instanceof ContractBatch
            && $record->attached_user_id === $user->id;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
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
