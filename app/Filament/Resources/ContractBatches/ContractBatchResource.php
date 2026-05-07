<?php

namespace App\Filament\Resources\ContractBatches;

use App\Filament\Resources\ContractBatches\Pages\CreateContractBatch;
use App\Filament\Resources\ContractBatches\Pages\EditContractBatch;
use App\Filament\Resources\ContractBatches\Pages\EditDocumentsContractBatch;
use App\Filament\Resources\ContractBatches\Pages\ListContractBatches;
use App\Filament\Resources\ContractBatches\Pages\ViewContractBatch;
use App\Filament\Resources\ContractBatches\Schemas\ContractBatchForm;
use App\Filament\Resources\ContractBatches\Schemas\ContractBatchInfolist;
use App\Filament\Resources\ContractBatches\Tables\ContractBatchesTable;
use App\Models\ContractBatch;
use App\Models\User;
use App\Services\ContractBatchService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
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
            'edit-documents' => EditDocumentsContractBatch::route('/{record}/edit/documents'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canViewAllContracts();
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canViewAllContracts();
    }

    public static function canEdit($record): bool
    {
        return static::canCreate() && static::canView($record);
    }

    public static function canDelete($record): bool
    {
        return static::canCreate() && static::canView($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::canCreate();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canViewAllContracts()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function makeAttachAction(): Action
    {
        return Action::make('attach')
            ->label('Прикачи')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('primary')
            ->visible(static fn (): bool => auth()->user()?->canViewAllContracts() ?? false)
            ->modalHeading('Прикачи договор към потребител')
            ->modalDescription('Изберете потребител, който ще има достъп до този пакет. Изпразнете полето, за да премахнете прикачването.')
            ->modalSubmitActionLabel('Запази')
            ->modalCancelActionLabel('Отказ')
            ->fillForm(static fn (ContractBatch $record): array => [
                'operator_id' => $record->attached_user_id,
            ])
            ->schema([
                Select::make('operator_id')
                    ->label('Потребител')
                    ->placeholder('— без прикачване —')
                    ->options(static fn (): array => User::query()
                        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ])
            ->action(function (array $data, ContractBatch $record): void {
                $actor = auth()->user();

                if (! $actor instanceof User) {
                    return;
                }

                $operator = filled($data['operator_id'] ?? null)
                    ? User::find($data['operator_id'])
                    : null;

                try {
                    app(ContractBatchService::class)->attachToOperator($record, $operator, $actor);
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title($operator !== null
                        ? sprintf('Договорът е прикачен към %s.', $operator->name)
                        : 'Прикачването е премахнато.')
                    ->success()
                    ->send();
            });
    }
}
