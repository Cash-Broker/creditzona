<x-filament-widgets::widget>
    <x-filament::section
        heading="История на получените заявки"
        description="Дневна справка по реално постъпилите заявки. Видима е само за администратор."
    >
        <x-slot name="afterHeader">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                    Общо {{ $leadCount }} {{ $leadCount === 1 ? 'заявка' : 'заявки' }}
                </span>

                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                    {{ $daysCount }} {{ $daysCount === 1 ? 'ден' : 'дни история' }}
                </span>
            </div>
        </x-slot>

        <div class="overflow-hidden rounded-[1.5rem] border border-gray-200 bg-white shadow-sm">
            @if ($rows->isEmpty())
                <div class="px-6 py-12 text-center">
                    <div class="text-sm font-semibold text-gray-900">
                        Още няма записани заявки
                    </div>

                    <div class="mt-2 text-sm text-gray-500">
                        Когато започнат да влизат заявки, тук автоматично ще се пази дневната история.
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Дата
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Ден
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Получени заявки
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($rows as $row)
                                <tr @class([
                                    'bg-emerald-50/40' => $row['is_today'],
                                ])>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">
                                        <div class="flex items-center gap-3">
                                            <span>{{ $row['date_label'] }}</span>

                                            @if ($row['is_today'])
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                    Днес
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ $row['weekday_label'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-800 ring-1 ring-inset ring-gray-200">
                                            {{ $row['total_leads'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
