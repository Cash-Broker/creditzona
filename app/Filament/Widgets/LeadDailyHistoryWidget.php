<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class LeadDailyHistoryWidget extends Widget
{
    protected string $view = 'filament.widgets.lead-daily-history-widget';

    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $rows = $this->getDailyHistoryRows();

        return [
            'rows' => $rows,
            'daysCount' => $rows->count(),
            'leadCount' => $rows->sum('total_leads'),
        ];
    }

    /**
     * @return Collection<int, array{
     *     date_key: string,
     *     date_label: string,
     *     weekday_label: string,
     *     total_leads: int,
     *     is_today: bool
     * }>
     */
    protected function getDailyHistoryRows(): Collection
    {
        $today = CarbonImmutable::now('Europe/Sofia')->toDateString();
        $dailyCounts = [];

        foreach (Lead::query()->select('created_at')->cursor() as $lead) {
            if ($lead->created_at === null) {
                continue;
            }

            $dateKey = $lead->created_at
                ->timezone('Europe/Sofia')
                ->toDateString();

            $dailyCounts[$dateKey] = ($dailyCounts[$dateKey] ?? 0) + 1;
        }

        krsort($dailyCounts);

        return collect($dailyCounts)
            ->map(static function (int $totalLeads, string $dateKey) use ($today): array {
                $date = CarbonImmutable::parse($dateKey, 'Europe/Sofia');

                return [
                    'date_key' => $dateKey,
                    'date_label' => $date->format('d.m.Y'),
                    'weekday_label' => mb_convert_case(
                        $date->locale('bg')->translatedFormat('l'),
                        MB_CASE_TITLE,
                        'UTF-8',
                    ),
                    'total_leads' => $totalLeads,
                    'is_today' => $dateKey === $today,
                ];
            })
            ->values();
    }
}
