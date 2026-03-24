<?php

namespace App\Filament\Resources\AttachedLeads;

use App\Filament\Resources\AttachedLeads\Pages\EditAttachedLead;
use App\Filament\Resources\AttachedLeads\Pages\ListAttachedLeads;
use App\Filament\Resources\AttachedLeads\Pages\ViewAttachedLead;
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

class AttachedLeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Закачени към мен';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'закачена заявка';

    protected static ?string $pluralModelLabel = 'закачени заявки';

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
        return LeadsTable::configure($table, static::class, isAttachedResource: true);
    }

    public static function makeReturnToPrimaryAction(): Action
    {
        return Action::make('return_to_primary')
            ->label('Върни')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Връщане към основния служител')
            ->modalDescription('Заявката ще бъде махната от "Закачени към мен" и ще остане само при основния служител.')
            ->successRedirectUrl(static::getUrl())
            ->visible(function (Lead $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && ($user->isAdmin() || $user->isOperator())
                    && $record->additional_user_id === $user->id;
            })
            ->action(function (Lead $record): void {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                try {
                    app(LeadService::class)->returnAttachedLeadToPrimary($record, $user);
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Заявката е върната към основния служител.')
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
            'index' => ListAttachedLeads::route('/'),
            'view' => ViewAttachedLead::route('/{record}'),
            'edit' => EditAttachedLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->attachedToUser($user);
    }
}
