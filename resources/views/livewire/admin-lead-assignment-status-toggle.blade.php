@php
    $onlineCount = collect($primaryOperatorStatuses)->where('is_online', true)->count();
    $teamTotalCount = count($primaryOperatorStatuses);
@endphp

<div
    wire:poll.visible.5s="syncState"
    x-data="{ teamOpen: false }"
    @click.outside="teamOpen = false"
    @keydown.escape.window="teamOpen = false"
    class="fi-admin-status-cluster"
>
    @if ($canToggleOwnAvailability || $canViewTeamAvailability)
        @if ($canToggleOwnAvailability)
            {{-- Mobile/tablet: compact pill toggle --}}
            <button
                type="button"
                wire:click="toggleAvailability"
                wire:loading.attr="disabled"
                @class([
                    'fi-admin-status-self-toggle',
                    'fi-admin-status-self-mobile',
                    'fi-admin-status-self-toggle--online' => $isAvailableForLeadAssignment,
                    'fi-admin-status-self-toggle--offline' => ! $isAvailableForLeadAssignment,
                ])
                title="{{ $isAvailableForLeadAssignment ? 'Натисни, за да излезеш офлайн' : 'Натисни, за да влезеш онлайн' }}"
            >
                <span class="fi-admin-status-self-toggle-dot"></span>
                <span>{{ $isAvailableForLeadAssignment ? 'Онлайн' : 'Офлайн' }}</span>
            </button>

            {{-- Desktop: full card --}}
            <div class="fi-admin-status-self-desktop rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-lg leading-none">
                            {{ $isAvailableForLeadAssignment ? '🟢' : '⚪' }}
                        </span>

                        <span class="text-sm font-semibold text-gray-900">
                            {{ $isAvailableForLeadAssignment ? 'Приемам нови заявки' : 'Не приемам нови заявки' }}
                        </span>
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
            {{-- Mobile/tablet: compact icon trigger + popover --}}
            <div class="fi-admin-status-team-mobile relative">
                <button
                    type="button"
                    @click="teamOpen = ! teamOpen"
                    :aria-expanded="teamOpen ? 'true' : 'false'"
                    class="fi-admin-status-team-trigger"
                    title="Екип за нови заявки"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="fi-admin-status-team-trigger-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    <span>{{ $onlineCount }}/{{ $teamTotalCount }}</span>
                </button>

                <div
                    x-cloak
                    x-show="teamOpen"
                    x-transition.opacity.duration.150ms
                    class="fi-admin-status-team-popover"
                >
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">
                        Екип за нови заявки
                    </p>

                    <div class="mt-2 flex flex-col gap-1.5">
                        @foreach ($primaryOperatorStatuses as $member)
                            <span
                                @class([
                                    'inline-flex items-center justify-between gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1',
                                    'bg-green-50 text-green-700 ring-green-200' => $member['is_online'],
                                    'bg-gray-100 text-gray-700 ring-gray-200' => ! $member['is_online'],
                                ])
                            >
                                <span class="flex items-center gap-2">
                                    <span>{{ $member['is_online'] ? '🟢' : '⚪' }}</span>
                                    <span>{{ $member['name'] }}</span>
                                </span>
                                <span>{{ $member['is_online'] ? 'Онлайн' : 'Офлайн' }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Desktop: full card --}}
            <div class="fi-admin-status-team-desktop rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-sm">
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
