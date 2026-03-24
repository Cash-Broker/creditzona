<div
    wire:poll.visible.5s="syncState"
    class="hidden items-center gap-4 lg:flex"
>
    @if ($canToggleOwnAvailability || $canViewTeamAvailability)
        @if ($canToggleOwnAvailability)
            <div class="rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-sm">
                <div class="flex items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg leading-none">
                                {{ $isAvailableForLeadAssignment ? '🟢' : '⚪' }}
                            </span>

                            <span class="text-sm font-semibold text-gray-900">
                                {{ $isAvailableForLeadAssignment ? 'Приемам нови заявки' : 'Не приемам нови заявки' }}
                            </span>
                        </div>
                    </div>

                    <x-filament::button
                        :color="$isAvailableForLeadAssignment ? 'gray' : 'success'"
                        :icon="$isAvailableForLeadAssignment ? 'heroicon-m-pause-circle' : 'heroicon-m-play-circle'"
                        size="sm"
                        wire:click="toggleAvailability"
                        wire:loading.attr="disabled"
                    >
                        {{ $isAvailableForLeadAssignment ? 'Излизам офлайн' : 'Влизам онлайн' }}
                    </x-filament::button>
                </div>
            </div>
        @endif

        @if ($canViewTeamAvailability)
            <div class="rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="pt-0.5 text-lg leading-none">👀</div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">
                            Екип за нови заявки
                        </p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($primaryOperatorStatuses as $member)
                                <span
                                    @class([
                                        'inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1',
                                        'bg-green-50 text-green-700 ring-green-200' => $member['is_online'],
                                        'bg-gray-100 text-gray-700 ring-gray-200' => ! $member['is_online'],
                                    ])
                                >
                                    <span>{{ $member['is_online'] ? '🟢' : '⚪' }}</span>
                                    <span>{{ $member['name'] }}</span>
                                    <span>{{ $member['is_online'] ? 'Онлайн' : 'Офлайн' }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
