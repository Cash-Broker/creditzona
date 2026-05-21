<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class MarketingLeadsByCreditTypeChartWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Разбивка по тип кредит';

    /**
     * @var array<string, string>
     */
    private const CREDIT_TYPE_LABELS = [
        Lead::CREDIT_TYPE_CONSUMER => 'Потребителски',
        Lead::CREDIT_TYPE_MORTGAGE => 'Ипотечен',
        Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR => 'С поръчител',
    ];

    /**
     * @var array<string, string>
     */
    private const CREDIT_TYPE_COLORS = [
        Lead::CREDIT_TYPE_CONSUMER => '#06b6d4',
        Lead::CREDIT_TYPE_MORTGAGE => '#f59e0b',
        Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR => '#8b5cf6',
    ];

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
        return 'doughnut';
    }

    protected function getData(): array
    {
        $range = $this->resolveRange();

        $counts = Lead::query()
            ->whereBetween('created_at', [$range['start']->utc(), $range['end']->utc()])
            ->selectRaw('credit_type, COUNT(*) as total')
            ->groupBy('credit_type')
            ->pluck('total', 'credit_type')
            ->all();

        $labels = [];
        $values = [];
        $colors = [];

        foreach (self::CREDIT_TYPE_LABELS as $key => $label) {
            $labels[] = $label;
            $values[] = (int) ($counts[$key] ?? 0);
            $colors[] = self::CREDIT_TYPE_COLORS[$key];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Запитвания',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
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
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '60%',
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
