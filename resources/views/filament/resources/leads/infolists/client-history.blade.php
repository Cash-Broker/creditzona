@php
    $state = $getState() ?? [];
    $submissions = is_array($state['submissions'] ?? null) ? $state['submissions'] : [];
@endphp

@if ($submissions === [])
    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500">
        Няма предишни заявки от този клиент.
    </div>
@else
    <div class="space-y-4">
        @foreach ($submissions as $submission)
            <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">
                        Заявка {{ $submission['reference'] }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                        {{ $submission['status_label'] }}
                    </span>
                    <span class="text-xs text-gray-500">{{ $submission['created_at'] }}</span>
                    @if (! empty($submission['credit_type_label']))
                        <span class="text-xs text-gray-500">• {{ $submission['credit_type_label'] }}</span>
                    @endif
                    @if (! empty($submission['assigned_user']))
                        <span class="text-xs text-gray-500">• Служител: {{ $submission['assigned_user'] }}</span>
                    @endif
                    @if (! empty($submission['url']))
                        <a href="{{ $submission['url'] }}" target="_blank" class="ml-auto text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                            Отвори заявката
                        </a>
                    @endif
                </div>

                <div>
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Поръчители</div>
                    @if ($submission['guarantors'] === [])
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                            Няма добавени поръчители
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($submission['guarantors'] as $guarantor)
                                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="{{ $guarantor['status_classes'] }}">{{ $guarantor['status_label'] }}</span>
                                        <span class="text-sm font-medium text-gray-700">{{ $guarantor['name'] }}</span>
                                        @if (! empty($guarantor['phone']))
                                            <span class="text-xs text-gray-500">{{ $guarantor['phone'] }}</span>
                                        @endif
                                        <span class="text-xs text-gray-400">ЕГН: {{ $guarantor['egn_masked'] }}</span>
                                    </div>
                                    @if ($guarantor['notes'] !== [])
                                        <div class="mt-3">
                                            @include('filament.resources.leads.infolists.note-history-entries', [
                                                'entries' => $guarantor['notes'],
                                                'emptyMessage' => 'Няма',
                                            ])
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">История на съобщенията</div>
                    @include('filament.resources.leads.infolists.note-history-entries', [
                        'entries' => $submission['notes'],
                        'emptyMessage' => 'Няма',
                    ])
                </div>
            </div>
        @endforeach
    </div>
@endif
