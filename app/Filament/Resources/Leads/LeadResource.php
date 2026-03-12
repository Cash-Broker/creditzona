<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Lead;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Заявки';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'заявка';

    protected static ?string $pluralModelLabel = 'заявки';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if (! $record instanceof Lead) {
            return parent::getRecordTitle($record);
        }

        return trim("{$record->first_name} {$record->last_name}");
    }

    public static function getCreditTypeOptions(): array
    {
        return [
            'consumer' => 'Потребителски кредит',
            'mortgage' => 'Ипотечен кредит',
        ];
    }

    public static function getPropertyTypeOptions(): array
    {
        return [
            'house' => 'Къща',
            'apartment' => 'Апартамент',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'new' => 'Нова',
            'in_progress' => 'В обработка',
            'processed' => 'Обработена',
        ];
    }

    public static function getCreditTypeLabel(?string $state): string
    {
        return static::getCreditTypeOptions()[$state] ?? ($state ?: 'Няма');
    }

    public static function getPropertyTypeLabel(?string $state): string
    {
        return static::getPropertyTypeOptions()[$state] ?? ($state ?: 'Няма');
    }

    public static function getStatusLabel(?string $state): string
    {
        return static::getStatusOptions()[$state] ?? ($state ?: 'Няма');
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
        return LeadsTable::configure($table);
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
            'index' => ListLeads::route('/'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
