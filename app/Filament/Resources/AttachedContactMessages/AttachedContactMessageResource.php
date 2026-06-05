<?php

namespace App\Filament\Resources\AttachedContactMessages;

use App\Filament\Resources\AttachedContactMessages\Pages\ListAttachedContactMessages;
use App\Filament\Resources\AttachedContactMessages\Pages\ViewAttachedContactMessage;
use App\Filament\Resources\ContactMessages\Schemas\ContactMessageInfolist;
use App\Filament\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\ContactMessageService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AttachedContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки и контакти';

    protected static ?string $navigationLabel = 'Съобщения към мен';

    protected static ?int $navigationSort = 14;

    protected static ?string $modelLabel = 'съобщение към мен';

    protected static ?string $pluralModelLabel = 'съобщения към мен';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isOperator()) {
            return null;
        }

        return (string) static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isOperator() || static::getEloquentQuery()->count() === 0) {
            return null;
        }

        // Когато операторът вече е в това меню, бройката остава в основния (син) цвят.
        // Иначе свети в червено, за да се набива на очи, че има съобщения към него.
        return request()->routeIs(static::getNavigationItemActiveRoutePattern())
            ? 'primary'
            : 'danger';
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table, isAttachedResource: true);
    }

    public static function makeReplyAction(): Action
    {
        return Action::make('reply')
            ->label('Отговори')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->modalHeading('Отговор към подателя')
            ->modalSubmitActionLabel('Изпрати')
            ->modalCancelActionLabel('Отказ')
            ->modalDescription(fn (ContactMessage $record): string => sprintf(
                'Отговорът ще бъде изпратен на %s от вашия имейл и подателят ще може да отговори директно на вас.',
                $record->email ?? '—',
            ))
            ->visible(function (ContactMessage $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && $user->isOperator()
                    && $record->assigned_user_id === $user->id
                    && $record->archived_at === null
                    && filled($record->email)
                    && filled($user->email);
            })
            ->schema([
                Textarea::make('body')
                    ->label('Съобщение')
                    ->required()
                    ->rows(8)
                    ->maxLength(5000)
                    ->autosize()
                    ->dehydrateStateUsing(static fn (?string $state): ?string => filled($state) ? trim($state) : null),
            ])
            ->action(function (array $data, ContactMessage $record): void {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                try {
                    app(ContactMessageService::class)->reply($record, $user, (string) ($data['body'] ?? ''));
                } catch (AuthorizationException|DomainException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Отговорът е изпратен.')
                    ->body('Съобщението замина към '.$record->email.'.')
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttachedContactMessages::route('/'),
            'view' => ViewAttachedContactMessage::route('/{record}'),
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
            && $record instanceof ContactMessage
            && $record->assigned_user_id === $user->id
            && $record->archived_at === null;
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
