<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Lead;
use App\Models\LeadGuarantor;
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

        return trim(implode(' ', array_filter([
            $record->first_name,
            $record->middle_name,
            $record->last_name,
        ])));
    }

    public static function getCreditTypeOptions(): array
    {
        return Lead::getCreditTypeOptions();
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
            'sms' => 'SMS',
            'email' => 'Имейл',
            'in_progress' => 'В обработка',
            'processed' => 'Обработена',
            'approved' => 'Одобрена',
            'rejected' => 'Отказана',
        ];
    }

    public static function getCreditTypeLabel(?string $state): string
    {
        return Lead::getCreditTypeLabel($state);
    }

    public static function getPropertyTypeLabel(?string $state): string
    {
        return static::getPropertyTypeOptions()[$state] ?? ($state ?: 'Няма');
    }

    public static function getStatusLabel(?string $state): string
    {
        return static::getStatusOptions()[$state] ?? ($state ?: 'Няма');
    }

    public static function getStatusBadgeColors(): array
    {
        return [
            'warning' => 'new',
            'gray' => ['sms', 'email'],
            'info' => 'in_progress',
            'success' => ['processed', 'approved'],
            'danger' => 'rejected',
        ];
    }

    public static function getMaritalStatusOptions(): array
    {
        return Lead::getMaritalStatusOptions();
    }

    public static function getMaritalStatusLabel(?string $state): string
    {
        return Lead::getMaritalStatusLabel($state);
    }

    public static function getGuarantorStatusOptions(): array
    {
        return LeadGuarantor::getStatusOptions();
    }

    public static function getGuarantorStatusLabel(?string $state): string
    {
        return LeadGuarantor::getStatusLabel($state);
    }

    public static function getPrimaryAssignmentOptions(): array
    {
        return User::query()
            ->eligibleForLeadPrimaryAssignment()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function getAdditionalAssignmentOptions(): array
    {
        return User::query()
            ->eligibleForLeadAdditionalAssignment()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->visibleToUser($user);
    }
}
