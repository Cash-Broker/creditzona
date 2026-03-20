@php
    $documents = $getState() ?? [];
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="space-y-3">
        @forelse ($documents as $document)
            @php
                $extension = strtoupper(pathinfo($document['name'], PATHINFO_EXTENSION));
                $badge = filled($extension) ? $extension : 'Файл';
                $url = $document['url'] ?? null;
                $downloadUrl = $document['download_url'] ?? null;
                $isPublicDocument = is_string($url) && filled($url);
                $resolvedUrl = $downloadUrl
                    ?? ($isPublicDocument
                        ? $url
                        : route('admin.leads.documents.download', ['lead' => $record, 'path' => $document['path']]));
                $description = $document['description'] ?? null;
            @endphp

            <div
                class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-gray-950/50">
                <div class="flex min-w-0 items-start gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-200 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-500/20">
                        {{ $badge }}
                    </div>

                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $document['name'] }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @if (filled($description))
                                {{ $description }}
                            @else
                                {{ $document['is_available'] ? 'Прикачен документ към клиента' : 'Файлът в момента не е достъпен' }}
                            @endif
                        </div>
                    </div>
                </div>

                @if ($document['is_available'])
                    <a href="{{ $resolvedUrl }}"
                        @if ($isPublicDocument)
                            target="_blank" rel="noopener noreferrer"
                        @endif
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-primary-200 bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-primary-500/20 dark:bg-white/5 dark:text-primary-300 dark:hover:bg-primary-500/10 dark:hover:text-primary-200 dark:focus:ring-offset-gray-950">
                        <span>{{ $isPublicDocument ? 'Отвори' : 'Изтегли' }}</span>
                    </a>
                @else
                    <span
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-500 ring-1 ring-inset ring-gray-200 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        <span>Недостъпен</span>
                    </span>
                @endif
            </div>
        @empty
            <div
                class="rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                Няма прикачени файлове.
            </div>
        @endforelse
    </div>
</x-dynamic-component>
