<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class MarketingLeadsByDayChartWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Запитвания по дни';

    protected string $color = 'info';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isMarketing();
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('start_date')
                    ->label('От дата')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->default(now('Europe/Sofia')->subDays(29)->startOfDay()),
                DatePicker::make('end_date')
                    ->label('До дата')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->default(now('Europe/Sofia')->endOfDay()),
            ])
            ->columns(2);
    }

    public function getDescription(): ?string
    {
        $range = $this->resolveRange();

        return sprintf(
            'Период: %s – %s',
            $range['start']->format('d.m.Y'),
            $range['end']->format('d.m.Y'),
        );
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $range = $this->resolveRange();
        $start = $range['start'];
        $end = $range['end'];

        $countsByDate = $this->normalizeCountsByDate($start, $end);

        Lead::query()
            ->whereBetween('created_at', [$start->utc(), $end->utc()])
            ->select(['created_at'])
            ->cursor()
            ->each(function (Lead $lead) use (&$countsByDate): void {
                if ($lead->created_at === null) {
                    return;
                }

                $bucket = $lead->created_at->timezone('Europe/Sofia')->toDateString();

                if (! array_key_exists($bucket, $countsByDate)) {
                    return;
                }

                $countsByDate[$bucket]++;
            });

        $labels = [];
        $values = [];

        foreach ($countsByDate as $date => $count) {
            $labels[] = CarbonImmutable::parse($date, 'Europe/Sofia')->format('d.m');
            $values[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Запитвания',
                    'data' => $values,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6, 182, 212, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function resolveRange(): array
    {
        $startInput = $this->filters['start_date'] ?? null;
        $endInput = $this->filters['end_date'] ?? null;

        $now = CarbonImmutable::now('Europe/Sofia');

        $start = is_string($startInput) && $startInput !== ''
            ? CarbonImmutable::parse($startInput, 'Europe/Sofia')->startOfDay()
            : $now->subDays(29)->startOfDay();

        $end = is_string($endInput) && $endInput !== ''
            ? CarbonImmutable::parse($endInput, 'Europe/Sofia')->endOfDay()
            : $now->endOfDay();

        if ($end->lessThan($start)) {
            $end = $start->endOfDay();
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array<string, int>
     */
    private function normalizeCountsByDate(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $counts = [];
        $cursor = $start;

        while ($cursor->lessThanOrEqualTo($end)) {
            $counts[$cursor->toDateString()] = 0;
            $cursor = $cursor->addDay();
        }

        return $counts;
    }
}
