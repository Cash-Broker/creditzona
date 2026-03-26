@php
    $state = $getState() ?? [];
    $entries = is_array($state['entries'] ?? null) ? $state['entries'] : [];
    $emptyMessage = $state['empty_message'] ?? 'Няма';
@endphp

@if ($entries === [])
    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500">
        {{ $emptyMessage }}
    </div>
@else
    <div class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50 p-3">
        @foreach ($entries as $entry)
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                @php
                    $meta = array_values(array_filter([
                        $entry['author'] ?? null,
                        $entry['timestamp'] ?? null,
                    ]));
                    $editedMeta = array_values(array_filter([
                        $entry['edited_by'] ?? null,
                        $entry['edited_at'] ?? null,
                    ]));
                @endphp

                @if ($meta !== [])
                    <div class="mb-2 text-xs font-semibold text-gray-500">{{ implode(' • ', $meta) }}</div>
                @endif

                <div class="whitespace-pre-wrap text-sm leading-6 text-gray-700">{{ $entry['body'] ?? '' }}</div>

                @if ($editedMeta !== [])
                    <div class="mt-3 text-xs text-gray-400">Редактирано: {{ implode(' • ', $editedMeta) }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif
