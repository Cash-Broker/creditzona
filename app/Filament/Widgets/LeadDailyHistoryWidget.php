<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class LeadDailyHistoryWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.lead-daily-history-widget';

    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array<string, string | null>
     */
    public ?array $filters = [
        'start_date' => null,
        'end_date' => null,
    ];

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function mount(): void
    {
        $this->form->fill($this->filters);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('start_date')
                    ->label('От дата')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->live(),
                DatePicker::make('end_date')
                    ->label('До дата')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->live(),
            ])
            ->columns(2)
            ->statePath('filters');
    }

    public function resetFilters(): void
    {
        $this->form->fill([
            'start_date' => null,
            'end_date' => null,
        ]);
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
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
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
            ->filter(static function (int $totalLeads, string $dateKey) use ($startDate, $endDate): bool {
                if (($startDate !== null) && ($dateKey < $startDate)) {
                    return false;
                }

                if (($endDate !== null) && ($dateKey > $endDate)) {
                    return false;
                }

                return true;
            })
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
