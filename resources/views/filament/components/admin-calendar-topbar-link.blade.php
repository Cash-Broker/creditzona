@php
    use App\Filament\Resources\CalendarEvents\CalendarEventResource;
    use Filament\Support\Icons\Heroicon;
@endphp

@if (CalendarEventResource::canViewAny())
    <ul class="fi-topbar-nav-groups">
        <x-filament-panels::topbar.item
            :url="CalendarEventResource::getUrl()"
            :active="request()->routeIs('filament.admin.resources.calendar-events.*')"
            :icon="Heroicon::OutlinedCalendarDays"
        >
            {{ CalendarEventResource::getNavigationLabel() }}
        </x-filament-panels::topbar.item>
    </ul>
@endif
