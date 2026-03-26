<?php

namespace App\Services\Contracts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class WorkingDayService
{
    public function addWorkingDays(CarbonInterface|string $date, int $days, string $timezone = 'Europe/Sofia'): CarbonImmutable
    {
        return $this->shiftWorkingDays($date, abs($days), $timezone, true);
    }

    public function subtractWorkingDays(CarbonInterface|string $date, int $days, string $timezone = 'Europe/Sofia'): CarbonImmutable
    {
        return $this->shiftWorkingDays($date, abs($days), $timezone, false);
    }

    private function shiftWorkingDays(
        CarbonInterface|string $date,
        int $days,
        string $timezone,
        bool $forward,
    ): CarbonImmutable {
        $current = $this->toImmutable($date, $timezone);

        if ($days === 0) {
            return $current;
        }

        $remainingDays = $days;

        while ($remainingDays > 0) {
            $current = $forward ? $current->addDay() : $current->subDay();

            if ($current->isWeekend()) {
                continue;
            }

            $remainingDays--;
        }

        return $current;
    }

    private function toImmutable(CarbonInterface|string $date, string $timezone): CarbonImmutable
    {
        if ($date instanceof CarbonInterface) {
            return CarbonImmutable::instance($date)->setTimezone($timezone)->startOfDay();
        }

        return CarbonImmutable::parse($date, $timezone)->startOfDay();
    }
}
