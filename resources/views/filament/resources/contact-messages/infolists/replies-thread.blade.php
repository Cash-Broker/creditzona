@php
    /** @var \App\Models\ContactMessage $record */
    $record = $getRecord();
    $replies = $record->replies()->with('sender:id,name,email')->get();
@endphp

<div class="space-y-3">
    @foreach ($replies as $reply)
        @php
            $authorName = $reply->sender?->name ?? 'Изтрит потребител';
            $avatarLetter = mb_strtoupper(mb_substr($authorName, 0, 1));
        @endphp

        <div class="flex items-start gap-3">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-600 text-sm font-semibold text-white shadow-sm">
                {{ $avatarLetter }}
            </div>

            <div class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-white/10 dark:bg-gray-900/60">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ $authorName }}
                    </span>

                    <span class="text-gray-400 dark:text-gray-500">
                        {{ $reply->from_email }} → {{ $reply->to_email }}
                    </span>

                    <span class="ms-auto text-gray-500 dark:text-gray-400">
                        {{ $reply->sent_at->timezone('Europe/Sofia')->format('d.m.Y H:i') }}
                    </span>
                </div>

                <div class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ $reply->subject }}
                </div>

                <div class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-gray-800 dark:text-gray-100">{{ $reply->body }}</div>
            </div>
        </div>
    @endforeach
</div>
