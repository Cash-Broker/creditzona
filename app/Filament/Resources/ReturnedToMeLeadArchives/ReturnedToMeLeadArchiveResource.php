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
use App\Services\LeadService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
        $table = LeadsTable::configure(
            $table,
            static::class,
            showReturnedMeta: true,
            showReturnedToMeArchiveMeta: true,
            defaultSortColumn: 'returned_to_primary_at',
        );

        return $table
            ->recordActions([
                static::makeApproveReturnedAction(),
                ...$table->getRecordActions(),
            ])
            ->toolbarActions([
                static::makeApproveReturnedBulkAction(),
                ...$table->getToolbarActions(),
            ]);
    }

    public static function makeApproveReturnedAction(): Action
    {
        return Action::make('approve_returned')
            ->label('Премести в одобрени върнати')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Преместване в одобрени върнати')
            ->modalDescription('Заявката ще бъде преместена в "Одобрени върнати".')
            ->visible(function (Lead $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && ($user->isAdmin() || $user->isOperator())
                    && $record->returned_to_primary_at !== null
                    && $record->approved_returned_at === null;
            })
            ->action(function (Lead $record): void {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                try {
                    app(LeadService::class)->approveReturnedLead($record, $user);
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Заявката е преместена в "Одобрени върнати".')
                    ->success()
                    ->send();
            });
    }

    public static function makeApproveReturnedBulkAction(): BulkAction
    {
        return BulkAction::make('approve_returned_selected')
            ->label('Премести в одобрени върнати')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $leadService = app(LeadService::class);
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                $records->each(fn (Lead $lead) => $leadService->approveReturnedLead($lead, $user));

                Notification::make()
                    ->title('Избраните заявки са преместени в "Одобрени върнати".')
                    ->success()
                    ->send();
            });
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
