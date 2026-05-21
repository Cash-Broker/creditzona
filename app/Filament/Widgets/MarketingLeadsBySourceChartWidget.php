<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class MarketingLeadsBySourceChartWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Топ канали (UTM source)';

    private const FALLBACK_LABEL = 'Директни / органични';

    private const MAX_RESULTS = 10;

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
        return 'bar';
    }

    protected function getData(): array
    {
        $range = $this->resolveRange();

        $rows = Lead::query()
            ->whereBetween('created_at', [$range['start']->utc(), $range['end']->utc()])
            ->selectRaw('COALESCE(NULLIF(utm_source, ?), ?) as bucket, COUNT(*) as total', ['', self::FALLBACK_LABEL])
            ->groupBy('bucket')
            ->orderByDesc('total')
            ->orderBy('bucket')
            ->limit(self::MAX_RESULTS)
            ->get();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = (string) $row->bucket;
            $values[] = (int) $row->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Запитвания',
                    'data' => $values,
                    'backgroundColor' => '#06b6d4',
                    'borderRadius' => 6,
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
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
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
}
