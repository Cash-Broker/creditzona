<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketingOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Брой запитвания от сайта';

    protected ?string $description = 'Обобщена статистика на постъпилите запитвания.';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isMarketing();
    }

    protected function getStats(): array
    {
        $now = CarbonImmutable::now('Europe/Sofia');

        return [
            $this->buildTodayStat($now),
            $this->buildWeekStat($now),
            $this->buildMonthStat($now),
            $this->buildTotalStat($now),
        ];
    }

    private function buildTodayStat(CarbonImmutable $now): Stat
    {
        $todayStart = $now->startOfDay();
        $yesterdayStart = $todayStart->subDay();

        $today = $this->countLeadsBetween($todayStart, $todayStart->endOfDay());
        $yesterday = $this->countLeadsBetween($yesterdayStart, $yesterdayStart->endOfDay());

        return Stat::make('Запитвания днес', $today)
            ->description($this->formatTrend($today, $yesterday, 'вчера'))
            ->descriptionIcon($this->trendIcon($today, $yesterday))
            ->chart($this->lastSevenDayCounts($now))
            ->color($this->trendColor($today, $yesterday, $today > 0));
    }

    private function buildWeekStat(CarbonImmutable $now): Stat
    {
        $weekStart = $now->startOfWeek();
        $lastWeekStart = $weekStart->subWeek();
        $lastWeekEnd = $weekStart->subSecond();

        $thisWeek = $this->countLeadsBetween($weekStart, $now);
        $lastWeek = $this->countLeadsBetween($lastWeekStart, $lastWeekEnd);

        return Stat::make('Запитвания тази седмица', $thisWeek)
            ->description($this->formatTrend($thisWeek, $lastWeek, 'миналата седмица'))
            ->descriptionIcon($this->trendIcon($thisWeek, $lastWeek))
            ->chart($this->lastSevenDayCounts($now))
            ->color($this->trendColor($thisWeek, $lastWeek, $thisWeek > 0));
    }

    private function buildMonthStat(CarbonImmutable $now): Stat
    {
        $monthStart = $now->startOfMonth();
        $lastMonthStart = $monthStart->subMonth();
        $lastMonthEnd = $monthStart->subSecond();

        $thisMonth = $this->countLeadsBetween($monthStart, $now);
        $lastMonth = $this->countLeadsBetween($lastMonthStart, $lastMonthEnd);

        return Stat::make('Запитвания този месец', $thisMonth)
            ->description($this->formatTrend($thisMonth, $lastMonth, 'миналия месец'))
            ->descriptionIcon($this->trendIcon($thisMonth, $lastMonth))
            ->chart($this->lastSevenDayCounts($now))
            ->color($this->trendColor($thisMonth, $lastMonth, $thisMonth > 0));
    }

    private function buildTotalStat(CarbonImmutable $now): Stat
    {
        $total = Lead::query()->count();

        return Stat::make('Всички запитвания', $total)
            ->description('Общо от стартирането на сайта')
            ->descriptionIcon(Heroicon::OutlinedClipboardDocumentList)
            ->chart($this->lastSevenDayCounts($now))
            ->color('warning');
    }

    private function countLeadsBetween(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return Lead::query()
            ->whereBetween('created_at', [$start->utc(), $end->utc()])
            ->count();
    }

    /**
     * @return array<int, int>
     */
    private function lastSevenDayCounts(CarbonImmutable $now): array
    {
        $counts = [];

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $day = $now->subDays($daysAgo)->startOfDay();
            $counts[] = $this->countLeadsBetween($day, $day->endOfDay());
        }

        return $counts;
    }

    private function formatTrend(int $current, int $previous, string $comparisonLabel): string
    {
        if ($previous === 0) {
            if ($current === 0) {
                return "Няма промяна спрямо {$comparisonLabel}";
            }

            return "+{$current} спрямо {$comparisonLabel}";
        }

        $diff = $current - $previous;
        $percent = (int) round(($diff / $previous) * 100);

        if ($diff === 0) {
            return "Без промяна спрямо {$comparisonLabel}";
        }

        $sign = $diff > 0 ? '+' : '';

        return "{$sign}{$percent}% спрямо {$comparisonLabel}";
    }

    private function trendIcon(int $current, int $previous): Heroicon
    {
        if ($current === $previous) {
            return Heroicon::OutlinedMinusSmall;
        }

        return $current > $previous
            ? Heroicon::OutlinedArrowTrendingUp
            : Heroicon::OutlinedArrowTrendingDown;
    }

    private function trendColor(int $current, int $previous, bool $hasActivity): string
    {
        if (! $hasActivity) {
            return 'gray';
        }

        if ($current === $previous) {
            return 'info';
        }

        return $current > $previous ? 'success' : 'danger';
    }
}
