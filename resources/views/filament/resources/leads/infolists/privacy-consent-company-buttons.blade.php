@php
    /** @var array{rekredo: ?string, d_consulting: ?string} $state */
    $state = $getState() ?? [];
    $rekredoUrl = $state['rekredo'] ?? null;
    $dConsultingUrl = $state['d_consulting'] ?? null;
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="flex flex-wrap items-center gap-2">
        @if (filled($rekredoUrl))
            <a href="{{ $rekredoUrl }}"
                class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-primary-500/20 dark:bg-white/5 dark:text-primary-300 dark:hover:bg-primary-500/10 dark:hover:text-primary-200 dark:focus:ring-offset-gray-950">
                <span>Свали ЛД (РеКредо)</span>
            </a>
        @endif

        @if (filled($dConsultingUrl))
            <a href="{{ $dConsultingUrl }}"
                class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-primary-500/20 dark:bg-white/5 dark:text-primary-300 dark:hover:bg-primary-500/10 dark:hover:text-primary-200 dark:focus:ring-offset-gray-950">
                <span>Свали ЛД (Д-консултинг)</span>
            </a>
        @endif
    </div>
</x-dynamic-component>
