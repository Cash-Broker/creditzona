<?php

namespace Tests\Unit;

use App\Services\Contracts\BulgarianDateFormatterService;
use App\Services\Contracts\BulgarianNumberToWordsService;
use PHPUnit\Framework\TestCase;

class BulgarianDateFormatterServiceTest extends TestCase
{
    public function test_it_formats_dates_in_bulgarian(): void
    {
        $service = new BulgarianDateFormatterService(new BulgarianNumberToWordsService);

        $this->assertSame('12.05.2026', $service->format('2026-05-12'));
        $this->assertSame('дванадесети май две хиляди двадесет и шеста година', $service->spellOut('2026-05-12'));
    }
}
