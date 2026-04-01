<x-filament-widgets::widget>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50/50 dark:border-white/10 dark:bg-gray-900/40">
        {{-- Chat area --}}
        <div
            class="h-96 overflow-y-auto px-4 py-3"
            x-data
            x-init="$el.scrollTop = $el.scrollHeight"
        >
            @if (empty($messages))
                <div class="flex h-full items-center justify-center">
                    <span class="text-sm text-gray-400 dark:text-gray-500">Няма съобщения</span>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($messages as $msg)
                        <div class="flex {{ $msg['isMe'] ? 'justify-end' : '' }}">
                            <div class="flex max-w-[80%] items-end gap-2 {{ $msg['isMe'] ? 'flex-row-reverse' : '' }}">
                                {{-- Avatar --}}
                                <div @class([
                                    'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold',
                                    'bg-primary-600 text-white' => $msg['isMe'],
                                    'bg-white text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10' => ! $msg['isMe'],
                                ])>{{ $msg['letter'] }}</div>

                                <div class="min-w-0">
                                    {{-- Author + time --}}
                                    <div @class([
                                        'mb-0.5 flex items-center gap-1.5 text-[11px]',
                                        'justify-end text-primary-600 dark:text-primary-400' => $msg['isMe'],
                                        'text-gray-400 dark:text-gray-500' => ! $msg['isMe'],
                                    ])>
                                        <span class="font-medium">{{ $msg['author'] }}</span>
                                        <span>{{ $msg['timestamp'] }}</span>
                                    </div>

                                    {{-- Bubble --}}
                                    <div @class([
                                        'min-w-16 rounded-2xl px-3 py-2 text-sm leading-relaxed',
                                        'rounded-br-sm bg-primary-600 text-white' => $msg['isMe'],
                                        'rounded-bl-sm bg-white text-gray-800 ring-1 ring-gray-200 dark:bg-gray-950 dark:text-gray-100 dark:ring-white/10' => ! $msg['isMe'],
                                    ])>
                                        @if ($editingIndex === $msg['index'])
                                            {{-- Edit mode --}}
                                            <textarea
                                                wire:model.live="editingBody"
                                                rows="2"
                                                class="w-full rounded-lg border-0 bg-white/20 p-1 text-sm leading-relaxed focus:ring-1 focus:ring-white/40 {{ $msg['isMe'] ? 'text-white placeholder-white/60' : 'text-gray-800 dark:text-gray-100' }}"
                                            ></textarea>
                                            <div class="mt-1 flex gap-1">
                                                <button
                                                    type="button"
                                                    wire:click="saveEdit"
                                                    @class([
                                                        'rounded px-2 py-0.5 text-[11px] font-medium',
                                                        'bg-white/20 text-white hover:bg-white/30' => $msg['isMe'],
                                                        'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200' => ! $msg['isMe'],
                                                    ])
                                                >Запази</button>
                                                <button
                                                    type="button"
                                                    wire:click="cancelEditing"
                                                    @class([
                                                        'rounded px-2 py-0.5 text-[11px] font-medium',
                                                        'text-white/70 hover:text-white' => $msg['isMe'],
                                                        'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300' => ! $msg['isMe'],
                                                    ])
                                                >Откажи</button>
                                                <button
                                                    type="button"
                                                    wire:click="deleteEntry({{ $msg['index'] }})"
                                                    wire:confirm="Сигурни ли сте, че искате да изтриете това съобщение?"
                                                    @class([
                                                        'rounded px-2 py-0.5 text-[11px] font-medium',
                                                        'text-red-300 hover:text-red-100' => $msg['isMe'],
                                                        'text-red-400 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300' => ! $msg['isMe'],
                                                    ])
                                                >Изтрий</button>
                                            </div>
                                        @else
                                            {{-- Read mode --}}
                                            <div class="whitespace-pre-wrap break-words">{{ $msg['body'] }}</div>

                                            @if (filled($msg['editedAt']) || filled($msg['editedBy']))
                                                <div class="mt-1 text-[10px] opacity-60">
                                                    редактирано {{ collect([$msg['editedBy'], $msg['editedAt']])->filter()->implode(' • ') }}
                                                </div>
                                            @endif

                                            @if ($msg['canEdit'])
                                                <button
                                                    type="button"
                                                    wire:click="startEditing({{ $msg['index'] }})"
                                                    class="mt-1 text-[10px] font-medium opacity-0 transition group-hover:opacity-100 {{ $msg['isMe'] ? 'text-white/60 hover:text-white' : 'text-gray-400 hover:text-gray-600' }}"
                                                >Редактирай</button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Input bar --}}
        <div class="border-t border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950">
            <div class="flex items-end gap-2">
                <textarea
                    wire:model.defer="newMessage"
                    rows="5"
                    placeholder="Добави бележка..."
                    style="height: 8rem; min-height: 8rem"
                    class="min-w-0 flex-1 resize-none rounded-xl border-gray-300 bg-gray-50/80 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900/70 dark:text-white"
                    x-on:keydown.enter.prevent="if (!$event.shiftKey) $wire.send()"
                ></textarea>
                <button
                    type="button"
                    wire:click="send"
                    class="shrink-0 self-end rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="send">Изпрати</span>
                    <span wire:loading wire:target="send">...</span>
                </button>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
