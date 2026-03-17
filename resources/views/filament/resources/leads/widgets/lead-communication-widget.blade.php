<x-filament-widgets::widget>
    @php
        $messageCount = $messages->count();
    @endphp

    <x-filament::section
        heading="Комуникация"
        description="Вътрешен чат по клиента."
    >
        <x-slot name="afterHeader">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/20">
                    Обновяване на 5 сек.
                </span>

                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10">
                    {{ $messageCount }} {{ $messageCount === 1 ? 'съобщение' : 'съобщения' }}
                </span>
            </div>
        </x-slot>

        <div class="space-y-5" wire:poll.5s>
            <div class="overflow-hidden rounded-[1.75rem] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-950">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                История на разговора
                            </p>

                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Всички вътрешни съобщения по този клиент се виждат тук.
                            </p>
                        </div>

                        @if ($messageCount > 0)
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Последно обновяване: автоматично
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-gray-100/70 p-5 dark:bg-gray-900/60">
                    <div class="max-h-[32rem] overflow-y-auto pr-1">
                        @if ($messageCount === 0)
                            <div class="flex min-h-64 flex-col items-center justify-center rounded-[1.5rem] border-2 border-dashed border-gray-300 bg-white px-6 py-10 text-center dark:border-white/10 dark:bg-gray-950/60">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-900 text-lg font-semibold text-white dark:bg-white dark:text-gray-900">
                                    ...
                                </div>

                                <div class="mt-4 text-base font-semibold text-gray-950 dark:text-white">
                                    Още няма съобщения
                                </div>

                                <div class="mt-2 max-w-md text-sm leading-6 text-gray-500 dark:text-gray-400">
                                    Когато някой админ напише съобщение, то ще се появи тук като част от историята по клиента.
                                </div>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($messages as $message)
                                    @php
                                        $isCurrentUser = $message->user_id === $currentUserId;
                                        $authorName = $message->author?->name ?? 'Изтрит потребител';
                                        $avatarLetter = mb_strtoupper(mb_substr($authorName, 0, 1));
                                    @endphp

                                    <div
                                        wire:key="lead-message-{{ $message->id }}"
                                        @class([
                                            'flex w-full',
                                            'justify-end' => $isCurrentUser,
                                        ])
                                    >
                                        <div
                                            @class([
                                                'flex max-w-3xl items-end gap-3',
                                                'flex-row-reverse' => $isCurrentUser,
                                            ])
                                        >
                                            <div
                                                @class([
                                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-sm font-semibold shadow-sm',
                                                    'bg-primary-600 text-white' => $isCurrentUser,
                                                    'bg-white text-gray-900 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-white dark:ring-white/10' => ! $isCurrentUser,
                                                ])
                                            >
                                                {{ $avatarLetter }}
                                            </div>

                                            <div class="min-w-0">
                                                <div
                                                    @class([
                                                        'mb-1 flex items-center gap-2 text-xs',
                                                        'justify-end text-primary-700 dark:text-primary-300' => $isCurrentUser,
                                                        'text-gray-500 dark:text-gray-400' => ! $isCurrentUser,
                                                    ])
                                                >
                                                    <span class="font-semibold">
                                                        {{ $isCurrentUser ? 'Вие' : $authorName }}
                                                    </span>

                                                    <span aria-hidden="true">&bull;</span>

                                                    <span>
                                                        {{ $message->created_at->timezone('Europe/Sofia')->format('d.m.Y H:i') }}
                                                    </span>
                                                </div>

                                                <div
                                                    @class([
                                                        'rounded-3xl px-5 py-4 text-sm leading-6 shadow-sm',
                                                        'rounded-br-lg bg-primary-600 text-white' => $isCurrentUser,
                                                        'rounded-bl-lg border border-gray-200 bg-white text-gray-800 dark:border-white/10 dark:bg-gray-950 dark:text-gray-100' => ! $isCurrentUser,
                                                    ])
                                                >
                                                    <div class="whitespace-pre-wrap break-words">
                                                        {{ $message->body }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-[1.5rem] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-950">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        Ново съобщение
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Напишете кратка вътрешна бележка към останалите админи по този клиент.
                    </p>
                </div>

                <form wire:submit="send" class="space-y-4 p-5">
                    {{ $this->form }}

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Историята се опреснява автоматично и не е нужен ръчен refresh.
                        </p>

                        <x-filament::button type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="send">
                                Изпрати съобщение
                            </span>

                            <span wire:loading wire:target="send">
                                Изпращане...
                            </span>
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
