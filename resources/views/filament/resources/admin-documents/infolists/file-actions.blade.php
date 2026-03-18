<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="space-y-4">
        <div class="rounded-[1.5rem] border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-950/60">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <div class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $record->original_file_name }}
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $record->fileExists() ? 'Файлът е наличен във вътрешното хранилище.' : 'Файлът в момента не е достъпен.' }}
                    </div>

                    @if ($record->fileExists() && ! $record->canBeOpenedInline())
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Този тип файл е достъпен само за сваляне.
                        </div>
                    @endif
                </div>

                @if ($record->fileExists())
                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if ($record->canBeOpenedInline())
                            <a
                                href="{{ route('admin.documents.open', $record) }}"
                                target="_blank"
                                rel="noreferrer"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm transition hover:border-primary-200 hover:text-primary-700 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-primary-500/40 dark:hover:text-primary-200"
                            >
                                <span>Отвори</span>
                            </a>
                        @endif

                        <a
                            href="{{ route('admin.documents.download', $record) }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-primary-600 via-cyan-600 to-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_16px_36px_-18px_rgba(8,145,178,0.95)] transition hover:-translate-y-0.5 hover:shadow-[0_20px_46px_-20px_rgba(14,116,144,1)]"
                        >
                            <span>Свали</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if ($record->fileExists() && $record->isPreviewableImage())
            <div class="overflow-hidden rounded-[1.5rem] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-950/60">
                <img
                    src="{{ route('admin.documents.open', $record) }}"
                    alt="{{ $record->original_file_name }}"
                    class="max-h-[32rem] w-full object-contain bg-gray-50 dark:bg-gray-900/80"
                >
            </div>
        @endif
    </div>
</x-dynamic-component>
