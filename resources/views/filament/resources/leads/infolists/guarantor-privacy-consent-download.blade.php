@php
    $document = $getState() ?? [];
    $url = $document['url'] ?? null;
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @if (filled($url))
        <a href="{{ $url }}"
            class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-primary-500/20 dark:bg-white/5 dark:text-primary-300 dark:hover:bg-primary-500/10 dark:hover:text-primary-200 dark:focus:ring-offset-gray-950">
            <span>Генерирай декларация</span>
        </a>
    @endif
</x-dynamic-component>
