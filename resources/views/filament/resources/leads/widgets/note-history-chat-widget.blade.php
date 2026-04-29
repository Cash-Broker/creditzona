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
                        <div class="group flex {{ $msg['isMe'] ? 'justify-end' : '' }}">
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
        <div
            class="border-t border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950"
            x-data="{
                msg: '',
                canSendMessage: @js($canSendMessage),
                draftStorageKey: @js($draftStorageKey),
                status: 'idle',
                errorMessage: '',
                feedbackTimer: null,
                init() {
                    if (! this.canSendMessage) {
                        return;
                    }

                    this.msg = localStorage.getItem(this.draftStorageKey) || '';

                    this.$watch('msg', (value) => {
                        if (value.trim()) {
                            localStorage.setItem(this.draftStorageKey, value);
                        } else {
                            localStorage.removeItem(this.draftStorageKey);
                        }

                        if (['sent', 'error'].includes(this.status)) {
                            this.status = 'idle';
                            this.errorMessage = '';
                        }
                    });
                },
                get hasDraft() {
                    return this.msg.trim().length > 0;
                },
                async sendMessage() {
                    if (! this.canSendMessage || ! this.hasDraft || this.status === 'sending') {
                        return;
                    }

                    const draft = this.msg.trim();

                    this.status = 'sending';
                    this.errorMessage = '';

                    try {
                        const wasSaved = await $wire.send(draft);

                        if (! wasSaved) {
                            this.status = 'error';
                            this.errorMessage = 'Не успяхме да запишем. Текстът остана като чернова - пробвайте пак.';

                            return;
                        }

                        this.msg = '';
                        localStorage.removeItem(this.draftStorageKey);
                        this.status = 'sent';

                        clearTimeout(this.feedbackTimer);
                        this.feedbackTimer = setTimeout(() => {
                            if (this.status === 'sent') {
                                this.status = 'idle';
                            }
                        }, 2500);
                    } catch (error) {
                        this.status = 'error';
                        this.errorMessage = 'Възникна проблем при запис. Текстът остана като чернова - пробвайте пак.';
                    }
                },
            }"
        >
            <div class="mb-2 flex items-center gap-2 text-xs">
                @if ($canSendMessage)
                    <span
                        class="h-2 w-2 rounded-full"
                        x-bind:class="{
                            'bg-green-500': (status === 'idle' && ! hasDraft) || status === 'sent',
                            'animate-pulse bg-primary-500': status === 'idle' && hasDraft,
                            'animate-pulse bg-amber-500': status === 'sending',
                            'bg-red-500': status === 'error',
                        }"
                    ></span>
                    <span class="text-gray-500 dark:text-gray-400" x-show="status === 'idle' && ! hasDraft">
                        Чатът е активен - можете да пишете бележка. Enter изпраща, Shift+Enter добавя нов ред.
                    </span>
                    <span class="font-medium text-primary-600 dark:text-primary-400" x-show="status === 'idle' && hasDraft" x-cloak>
                        Чернова - още не е изпратена. Натиснете „Изпрати“ или Enter, за да се запише.
                    </span>
                    <span class="font-medium text-amber-600 dark:text-amber-300" x-show="status === 'sending'" x-cloak>
                        Записване...
                    </span>
                    <span class="font-medium text-green-600 dark:text-green-400" x-show="status === 'sent'" x-cloak>
                        Записано успешно.
                    </span>
                    <span class="font-medium text-red-600 dark:text-red-400" x-show="status === 'error'" x-text="errorMessage" x-cloak></span>
                @else
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    <span class="text-amber-700 dark:text-amber-300">{{ $inactiveMessage }}</span>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <textarea
                    x-model="msg"
                    rows="5"
                    placeholder="{{ $canSendMessage ? 'Добави бележка...' : $inactiveMessage }}"
                    style="height: 8rem; min-height: 8rem"
                    class="min-w-0 flex-1 resize-none rounded-xl border-gray-300 bg-gray-50/80 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900/70 dark:text-white"
                    x-bind:class="{
                        'border-primary-400 ring-1 ring-primary-200 dark:border-primary-500 dark:ring-primary-500/30': hasDraft && status !== 'error',
                        'border-red-400 ring-1 ring-red-200 dark:border-red-500 dark:ring-red-500/30': status === 'error',
                    }"
                    x-bind:disabled="!canSendMessage"
                    wire:loading.attr="disabled"
                    wire:target="send"
                    x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                ></textarea>
                <button
                    type="button"
                    x-on:click="sendMessage()"
                    class="shrink-0 self-end rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
                    x-bind:disabled="!canSendMessage || !hasDraft || status === 'sending'"
                    wire:loading.attr="disabled"
                    wire:target="send"
                >
                    <span x-show="status !== 'sending'">Изпрати</span>
                    <span x-show="status === 'sending'" x-cloak>Записва...</span>
                </button>
            </div>
            @if ($canSendMessage)
                <div class="mt-1 text-[11px] text-gray-400 dark:text-gray-500" x-show="hasDraft && status !== 'sent'" x-cloak>
                    Черновата се пази в браузъра, докато съобщението не бъде записано.
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
