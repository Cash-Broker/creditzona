@php
    $state = $getState() ?? [];
    $entries = is_array($state['entries'] ?? null) ? $state['entries'] : [];
    $emptyMessage = $state['empty_message'] ?? 'Няма';
@endphp

@include('filament.resources.leads.infolists.note-history-entries', [
    'entries' => $entries,
    'emptyMessage' => $emptyMessage,
])
