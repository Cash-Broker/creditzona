<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="space-y-4">
        @if ($record->archiveExists())
            <div class="rounded-[1.5rem] border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-950/60">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">
                            Архив на пакета
                        </div>

                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Един ZIP файл с всички генерирани документи.
                        </div>
                    </div>

                    <a
                        href="{{ route('admin.contract-batches.archive.download', $record) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-primary-600 via-cyan-600 to-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_16px_36px_-18px_rgba(8,145,178,0.95)] transition hover:-translate-y-0.5 hover:shadow-[0_20px_46px_-20px_rgba(14,116,144,1)]"
                    >
                        <span>Свали пакет</span>
                    </a>
                </div>
            </div>
        @endif

        <div class="space-y-3">
            @foreach ($record->getGeneratedDocumentsForDisplay() as $document)
                <div class="rounded-[1.5rem] border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-950/60">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="space-y-1">
                            <div class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $document['label'] }}
                            </div>

                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Налични формати: {{ implode(', ', array_map(static fn (array $variant): string => strtoupper($variant['format']), $document['variants'] ?? [])) }}
                            </div>
                        </div>

                        @if ($document['is_available'])
                            <div class="flex flex-wrap gap-2">
                                @foreach (($document['variants'] ?? []) as $variant)
                                    @if ($variant['is_available'] ?? false)
                                        <a
                                            href="{{ route('admin.contract-batches.documents.download', [$record, $document['document_key'], 'format' => $variant['format']]) }}"
                                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm transition hover:border-primary-200 hover:text-primary-700 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-primary-500/40 dark:hover:text-primary-200"
                                        >
                                            <span>Свали {{ strtoupper($variant['format']) }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="text-xs text-red-600 dark:text-red-300">
                                Файлът не е наличен в хранилището.
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
