<?php

namespace Tests\Unit;

use App\Services\Contracts\WorkingDayService;
use PHPUnit\Framework\TestCase;

class WorkingDayServiceTest extends TestCase
{
    public function test_it_subtracts_working_days_while_skipping_weekends(): void
    {
        $service = new WorkingDayService;

        $result = $service->subtractWorkingDays('2026-03-30', 2);

        $this->assertSame('2026-03-26', $result->format('Y-m-d'));
    }

    public function test_it_adds_working_days_while_skipping_weekends(): void
    {
        $service = new WorkingDayService;

        $result = $service->addWorkingDays('2026-03-27', 2);

        $this->assertSame('2026-03-31', $result->format('Y-m-d'));
    }
}
