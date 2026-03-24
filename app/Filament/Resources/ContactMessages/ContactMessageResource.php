<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\ContactMessages\Schemas\ContactMessageInfolist;
use App\Filament\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\ContactMessageService;
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
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Контактни съобщения';

    protected static ?int $navigationSort = 13;

    protected static ?string $modelLabel = 'контактно съобщение';

    protected static ?string $pluralModelLabel = 'контактни съобщения';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getAssignmentOptions(): array
    {
        return User::query()
            ->where('role', User::ROLE_OPERATOR)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function makeAssignAction(): Action
    {
        return Action::make('assign_to_operator')
            ->label('Закачи')
            ->icon(Heroicon::OutlinedPaperClip)
            ->color('primary')
            ->fillForm(fn (ContactMessage $record): array => [
                'assigned_user_id' => $record->assigned_user_id,
            ])
            ->form([
                Select::make('assigned_user_id')
                    ->label('Оператор')
                    ->options(static::getAssignmentOptions())
                    ->required()
                    ->searchable()
                    ->native(false),
            ])
            ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isAdmin())
            ->action(function (ContactMessage $record, array $data): void {
                $actor = auth()->user();

                if (! $actor instanceof User) {
                    return;
                }

                $operator = User::query()->find($data['assigned_user_id'] ?? null);

                if (! $operator instanceof User) {
                    Notification::make()
                        ->title('Изберете оператор.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    app(ContactMessageService::class)->assignToOperator($record, $operator, $actor);
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title("Съобщението е закачено към {$operator->name}.")
                    ->success()
                    ->send();
            });
    }

    public static function makeArchiveAction(): Action
    {
        return Action::make('archive_contact_message')
            ->label('Архив')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Архивиране на съобщение')
            ->modalDescription('Съобщението ще бъде преместено в "Архив на съобщения".')
            ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isAdmin())
            ->action(function (ContactMessage $record): void {
                $actor = auth()->user();

                if (! $actor instanceof User) {
                    return;
                }

                try {
                    app(ContactMessageService::class)->archiveMessage($record, $actor);
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Съобщението е архивирано.')
                    ->success()
                    ->send();
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table);
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
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->isAdmin()
            && $record instanceof ContactMessage
            && $record->archived_at === null;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->active();
    }
}
