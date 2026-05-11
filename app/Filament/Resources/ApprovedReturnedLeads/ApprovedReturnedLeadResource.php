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
        $table = LeadsTable::configure(
            $table,
            static::class,
            isApprovedReturnedResource: true,
            showReturnedMeta: true,
            defaultSortColumn: 'approved_returned_at',
        );

        return $table->toolbarActions([
            static::makeArchiveBulkAction(),
            ...$table->getToolbarActions(),
        ]);
    }

    public static function makeArchiveBulkAction(): BulkAction
    {
        return BulkAction::make('archive_approved_returned_selected')
            ->label('Архивирай избраните')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Архивиране на избраните одобрени върнати заявки')
            ->modalDescription('Избраните заявки ще бъдат преместени в "Архивирани одобрени върнати". Заявки, които не са към вас, ще бъдат пропуснати.')
            ->deselectRecordsAfterCompletion()
            ->visible(function (): bool {
                $user = auth()->user();

                return $user instanceof User && $user->isOperator();
            })
            ->action(function (Collection $records): void {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                $leadService = app(LeadService::class);
                $archived = 0;
                $skipped = 0;

                foreach ($records as $record) {
                    if (! $record instanceof Lead) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $leadService->archiveApprovedReturnedLead($record, $user);
                        $archived++;
                    } catch (AuthorizationException|DomainException) {
                        $skipped++;
                    }
                }

                if ($archived === 0) {
                    Notification::make()
                        ->title('Нито една заявка не беше архивирана.')
                        ->warning()
                        ->send();

                    return;
                }

                $title = $skipped > 0
                    ? sprintf('Архивирани са %d от %d заявки.', $archived, $archived + $skipped)
                    : 'Избраните заявки са архивирани.';

                Notification::make()
                    ->title($title)
                    ->success()
                    ->send();
            });
    }

    public static function makeArchiveAction(): Action
    {
        return Action::make('archive_approved_returned')
            ->label('Архивирай')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Архивиране на одобрена върната заявка')
            ->modalDescription('Заявката ще бъде махната от "Одобрени върнати" и ще се премести в "Архивирани одобрени върнати".')
            ->visible(function (Lead $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && $user->isOperator()
                    && $record->assigned_user_id === $user->id
                    && $record->additional_user_id === null
                    && $record->approved_returned_at !== null
                    && $record->approved_returned_archived_at === null;
            })
            ->action(function (Lead $record): void {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                try {
                    app(\App\Services\LeadService::class)->archiveApprovedReturnedLead($record, $user);
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Заявката е архивирана и вече е в "Архивирани одобрени върнати".')
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
