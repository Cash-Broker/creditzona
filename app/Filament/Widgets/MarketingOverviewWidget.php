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

        $todayCount = Lead::query()
            ->whereBetween('created_at', [
                $now->startOfDay()->utc(),
                $now->endOfDay()->utc(),
            ])
            ->count();

        $weekCount = Lead::query()
            ->where('created_at', '>=', $now->startOfWeek()->utc())
            ->count();

        $monthCount = Lead::query()
            ->where('created_at', '>=', $now->startOfMonth()->utc())
            ->count();

        $totalCount = Lead::query()->count();

        return [
            Stat::make('Запитвания днес', $todayCount)
                ->description('От 00:00 ч. до 23:59 ч. (Sofia)')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color($todayCount > 0 ? 'success' : 'gray'),
            Stat::make('Запитвания тази седмица', $weekCount)
                ->description('От началото на седмицата')
                ->icon(Heroicon::OutlinedChartBar)
                ->color($weekCount > 0 ? 'info' : 'gray'),
            Stat::make('Запитвания този месец', $monthCount)
                ->description($this->getMonthLabel($now))
                ->icon(Heroicon::OutlinedChartPie)
                ->color($monthCount > 0 ? 'primary' : 'gray'),
            Stat::make('Всички запитвания', $totalCount)
                ->description('Общо от стартирането на сайта')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('warning'),
        ];
    }

    private function getMonthLabel(CarbonImmutable $now): string
    {
        return mb_convert_case(
            $now->locale('bg')->translatedFormat('F Y'),
            MB_CASE_TITLE,
            'UTF-8',
        );
    }
}
