<?php

namespace App\Filament\Resources\ContractBatches;

use App\Filament\Resources\ContractBatches\Pages\CreateContractBatch;
use App\Filament\Resources\ContractBatches\Pages\EditContractBatch;
use App\Filament\Resources\ContractBatches\Pages\ListContractBatches;
use App\Filament\Resources\ContractBatches\Pages\ViewContractBatch;
use App\Filament\Resources\ContractBatches\Schemas\ContractBatchForm;
use App\Filament\Resources\ContractBatches\Schemas\ContractBatchInfolist;
use App\Filament\Resources\ContractBatches\Tables\ContractBatchesTable;
use App\Models\ContractBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ContractBatchResource extends Resource
{
    protected static ?string $model = ContractBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Документи';

    protected static ?string $navigationLabel = 'Генерирани договори';

    protected static ?int $navigationSort = 21;

    protected static ?string $modelLabel = 'договорен пакет';

    protected static ?string $pluralModelLabel = 'договорни пакети';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'client_full_name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if (! $record instanceof ContractBatch) {
            return parent::getRecordTitle($record);
        }

        return $record->getDisplayTitle();
    }

    public static function form(Schema $schema): Schema
    {
        return ContractBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContractBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractBatchesTable::configure($table);
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
            'index' => ListContractBatches::route('/'),
            'create' => CreateContractBatch::route('/create'),
            'view' => ViewContractBatch::route('/{record}'),
            'edit' => EditContractBatch::route('/{record}/edit'),
        ];
    }
}
