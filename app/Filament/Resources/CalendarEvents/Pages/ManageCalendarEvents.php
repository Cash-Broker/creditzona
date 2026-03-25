<?php

namespace App\Filament\Resources\CalendarEvents\Pages;

use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use App\Filament\Resources\CalendarEvents\Schemas\CalendarEventForm;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\CalendarEventService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

class ManageCalendarEvents extends Page
{
    protected static string $resource = CalendarEventResource::class;

    protected static ?string $title = 'Календар';

    protected ?string $heading = 'Календар';

    protected string $view = 'filament.resources.calendar-events.pages.manage-calendar-events';

    public function createEventAction(): Action
    {
        return Action::make('createEvent')
            ->label('Ново събитие')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->authorize(fn (): bool => auth()->user() instanceof User && auth()->user()->can('create', CalendarEvent::class))
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge)
            ->modalHeading('Ново събитие')
            ->fillForm(function (array $arguments): array {
                $startsAt = filled($arguments['starts_at'] ?? null)
                    ? Carbon::parse((string) $arguments['starts_at'], config('app.timezone'))->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s');
                $endsAt = filled($arguments['ends_at'] ?? null)
                    ? Carbon::parse((string) $arguments['ends_at'], config('app.timezone'))->format('Y-m-d H:i:s')
                    : now()->addHour()->format('Y-m-d H:i:s');

                return [
                    'title' => '',
                    'description' => null,
                    'location' => null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'all_day' => (bool) ($arguments['all_day'] ?? false),
                    'event_type' => CalendarEvent::TYPE_APPOINTMENT,
                    'status' => CalendarEvent::STATUS_SCHEDULED,
                    'color' => null,
                    'user_id' => $arguments['user_id'] ?? auth()->id(),
                ];
            })
            ->schema($this->getEventActionSchema())
            ->action(function (array $data): void {
                /** @var User|null $actor */
                $actor = auth()->user();

                if (! $actor instanceof User) {
                    return;
                }

                app(CalendarEventService::class)->createEvent($data, $actor);

                Notification::make()
                    ->title('Събитието е създадено.')
                    ->success()
                    ->send();

                $this->dispatch('admin-calendar-refresh');
            });
    }

    public function editEventAction(): Action
    {
        return Action::make('editEvent')
            ->label('Редакция')
            ->icon('heroicon-o-pencil-square')
            ->record(fn (array $arguments): CalendarEvent => $this->resolveEventRecord($arguments))
            ->authorize(function (CalendarEvent $record): bool {
                $user = auth()->user();

                return $user instanceof User && $user->can('update', $record);
            })
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge)
            ->modalHeading('Редакция на събитие')
            ->fillForm(fn (CalendarEvent $record): array => [
                'title' => $record->title,
                'description' => $record->description,
                'location' => $record->location,
                'starts_at' => $record->starts_at?->format('Y-m-d H:i:s'),
                'ends_at' => $record->all_day
                    ? $record->ends_at?->copy()->addSecond()?->format('Y-m-d H:i:s')
                    : $record->ends_at?->format('Y-m-d H:i:s'),
                'all_day' => $record->all_day,
                'event_type' => $record->event_type,
                'status' => $record->status,
                'color' => $record->color,
                'user_id' => $record->user_id,
            ])
            ->schema($this->getEventActionSchema())
            ->action(function (CalendarEvent $record, array $data): void {
                /** @var User|null $actor */
                $actor = auth()->user();

                if (! $actor instanceof User) {
                    return;
                }

                app(CalendarEventService::class)->updateEvent($record, $data, $actor);

                Notification::make()
                    ->title('Събитието е обновено.')
                    ->success()
                    ->send();

                $this->dispatch('admin-calendar-refresh');
            });
    }

    public function deleteEventAction(): Action
    {
        return Action::make('deleteEvent')
            ->label('Изтрий')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->record(fn (array $arguments): CalendarEvent => $this->resolveEventRecord($arguments))
            ->authorize(function (CalendarEvent $record): bool {
                $user = auth()->user();

                return $user instanceof User && $user->can('delete', $record);
            })
            ->requiresConfirmation()
            ->modalHeading('Изтриване на събитие')
            ->modalDescription('Сигурни ли сте, че искате да изтриете това събитие?')
            ->action(function (CalendarEvent $record): void {
                app(CalendarEventService::class)->deleteEvent($record);

                Notification::make()
                    ->title('Събитието е изтрито.')
                    ->success()
                    ->send();

                $this->dispatch('admin-calendar-refresh');
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createEventAction(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $defaultUserFilter = $user instanceof User && $user->isOperator() ? $user->id : null;

        return [
            'calendarConfig' => [
                'feedUrl' => route('admin.calendar-events.feed'),
                'timingUrlTemplate' => route('admin.calendar-events.timing.update', ['calendarEvent' => '__CALENDAR_EVENT__']),
                'defaultUserFilter' => $defaultUserFilter,
                'currentUserId' => auth()->id(),
                'canCreate' => $user instanceof User && $user->can('create', CalendarEvent::class),
                'eventTypeOptions' => CalendarEvent::getEventTypeOptions(),
                'eventTypeColors' => CalendarEvent::getDefaultTypeColors(),
                'statusOptions' => CalendarEvent::getStatusOptions(),
                'userOptions' => CalendarEventResource::getUserFilterOptions(),
            ],
        ];
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    private function getEventActionSchema(): array
    {
        $user = auth()->user();

        return CalendarEventForm::schema(
            $user instanceof User && $user->isAdmin(),
            CalendarEventResource::getUserFilterOptions(),
        );
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function resolveEventRecord(array $arguments): CalendarEvent
    {
        $eventId = $arguments['event'] ?? $arguments['id'] ?? null;

        if (! is_numeric($eventId)) {
            throw (new ModelNotFoundException)->setModel(CalendarEvent::class);
        }

        return CalendarEvent::query()->findOrFail((int) $eventId);
    }
}
