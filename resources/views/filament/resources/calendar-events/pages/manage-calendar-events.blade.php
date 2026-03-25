<x-filament-panels::page>
    <div
        x-data="window.creditzonaCalendarPage(@js($calendarConfig))"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="space-y-1.5">
                        <h2 class="text-lg font-semibold text-gray-950">Вътрешен календар</h2>
                        <p class="text-sm text-gray-500">
                            Месец, седмица и ден в един работен календар с drag/drop и бързо редактиране.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="space-y-1.5">
                            <span class="text-sm font-medium text-gray-700">Потребител</span>
                            <select
                                x-model="filters.userId"
                                @change="refetchEvents()"
                                class="block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                            >
                                <option value="">Всички</option>
                                <template x-for="[value, label] in Object.entries(config.userOptions)" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </label>

                        <label class="space-y-1.5">
                            <span class="text-sm font-medium text-gray-700">Тип</span>
                            <select
                                x-model="filters.eventType"
                                @change="refetchEvents()"
                                class="block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                            >
                                <option value="">Всички</option>
                                <template x-for="[value, label] in Object.entries(config.eventTypeOptions)" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </label>

                        <label class="space-y-1.5">
                            <span class="text-sm font-medium text-gray-700">Статус</span>
                            <select
                                x-model="filters.status"
                                @change="refetchEvents()"
                                class="block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                            >
                                <option value="">Всички</option>
                                <template x-for="[value, label] in Object.entries(config.statusOptions)" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <template x-for="[value, label] in Object.entries(config.eventTypeOptions)" :key="value">
                        <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700">
                            <span
                                class="h-2.5 w-2.5 rounded-full"
                                :style="{ backgroundColor: config.eventTypeColors[value] ?? '#2563eb' }"
                            ></span>
                            <span x-text="label"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-950">Как работи</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    <li>Клик върху ден или часови слот: ново събитие.</li>
                    <li>Клик върху събитие: детайли и редакция.</li>
                    <li>Плъзни събитие: местиш дата и час.</li>
                    <li>Разпъни надолу: сменяш продължителността.</li>
                </ul>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div wire:ignore class="calendar-shell">
                <div x-ref="calendar" class="cz-admin-calendar"></div>
            </div>
        </div>

        <div
            x-cloak
            x-show="isDrawerOpen"
            x-transition.opacity
            class="fixed inset-0 z-[70] flex justify-end"
        >
            <div class="absolute inset-0 bg-gray-950/30" @click="closeDrawer()"></div>

            <div
                class="relative flex h-full w-full max-w-xl flex-col overflow-y-auto border-l border-gray-200 bg-white shadow-2xl"
                @keydown.escape.window="closeDrawer()"
            >
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                                :style="{ backgroundColor: selectedEvent?.color ?? '#2563eb' }"
                                x-text="selectedEvent?.eventTypeLabel ?? ''"
                            ></span>
                            <span
                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"
                                x-text="selectedEvent?.statusLabel ?? ''"
                            ></span>
                        </div>

                        <h3 class="text-xl font-semibold text-gray-950" x-text="selectedEvent?.title ?? ''"></h3>
                        <p class="text-sm text-gray-500" x-text="selectedEventDateLabel"></p>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                        @click="closeDrawer()"
                    >
                        <span class="sr-only">Затвори</span>
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex-1 space-y-5 px-6 py-5">
                    <template x-if="selectedEvent?.userName">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Потребител</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="selectedEvent.userName"></div>
                        </div>
                    </template>

                    <template x-if="selectedEvent?.location">
                        <div class="space-y-1.5">
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Локация</div>
                            <div class="text-sm leading-6 text-gray-800" x-text="selectedEvent.location"></div>
                        </div>
                    </template>

                    <template x-if="selectedEvent?.description">
                        <div class="space-y-1.5">
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Описание</div>
                            <div class="whitespace-pre-line text-sm leading-6 text-gray-800" x-text="selectedEvent.description"></div>
                        </div>
                    </template>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <template x-if="selectedEvent?.reminderLabel">
                            <div class="rounded-2xl border border-gray-200 px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Напомняне</div>
                                <div class="mt-1 text-sm font-medium text-gray-900" x-text="selectedEvent.reminderLabel"></div>
                            </div>
                        </template>

                        <template x-if="selectedEvent?.createdBy">
                            <div class="rounded-2xl border border-gray-200 px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Създадено от</div>
                                <div class="mt-1 text-sm font-medium text-gray-900" x-text="selectedEvent.createdBy"></div>
                            </div>
                        </template>

                        <template x-if="selectedEvent?.updatedBy">
                            <div class="rounded-2xl border border-gray-200 px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Последна редакция</div>
                                <div class="mt-1 text-sm font-medium text-gray-900" x-text="selectedEvent.updatedBy"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-6 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="closeDrawer()"
                            >
                                Затвори
                            </button>
                        </div>

                        <div class="flex gap-3" x-show="selectedEvent?.canManage">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500"
                                @click="$wire.mountAction('editEvent', { event: selectedEvent.id })"
                            >
                                Редакция
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-danger-200 bg-danger-50 px-4 py-2 text-sm font-semibold text-danger-700 transition hover:bg-danger-100"
                                @click="$wire.mountAction('deleteEvent', { event: selectedEvent.id })"
                            >
                                Изтрий
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
