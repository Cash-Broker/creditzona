<?php

namespace App\Filament\Resources\ReturnedToMeLeads;

use App\Filament\Resources\Leads\LeadResource as BaseLeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Filament\Resources\ReturnedToMeLeads\Pages\EditReturnedToMeLead;
use App\Filament\Resources\ReturnedToMeLeads\Pages\ListReturnedToMeLeads;
use App\Filament\Resources\ReturnedToMeLeads\Pages\ViewReturnedToMeLead;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
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

class ReturnedToMeLeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Върнати към мен';

    protected static ?int $navigationSort = 13;

    protected static ?string $modelLabel = 'върната към мен заявка';

    protected static ?string $pluralModelLabel = 'върнати към мен заявки';

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
            && $record->assigned_user_id === $user->id
            && $record->additional_user_id === null
            && $record->returned_to_primary_at !== null
            && $record->returned_to_primary_archived_at === null;
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
        return LeadsTable::configure($table, static::class, isReturnedToMeResource: true, showReturnedMeta: true);
    }

    public static function makeArchiveAction(): Action
    {
        return Action::make('archive_returned_to_me')
            ->label('Архивирай')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Архивиране на върната заявка')
            ->modalDescription('Заявката ще бъде махната от "Върнати към мен" и ще се премести в "Архивирани върнати към мен".')
            ->visible(function (Lead $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && $user->isOperator()
                    && $record->assigned_user_id === $user->id
                    && $record->additional_user_id === null
                    && $record->returned_to_primary_at !== null;
            })
            ->action(function (Lead $record): void {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                try {
                    app(\App\Services\LeadService::class)->archiveReturnedToPrimaryLead($record, $user);
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Заявката е архивирана и вече е в "Архивирани върнати към мен".')
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
            'index' => ListReturnedToMeLeads::route('/'),
            'view' => ViewReturnedToMeLead::route('/{record}'),
            'edit' => EditReturnedToMeLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->returnedToPrimaryUser($user);
    }
}
