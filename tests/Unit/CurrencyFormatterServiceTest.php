<?php

namespace Tests\Unit;

use App\Services\Contracts\BulgarianNumberToWordsService;
use App\Services\Contracts\CurrencyFormatterService;
use PHPUnit\Framework\TestCase;

class CurrencyFormatterServiceTest extends TestCase
{
    public function test_it_formats_euro_and_bgn_amounts(): void
    {
        $service = new CurrencyFormatterService(new BulgarianNumberToWordsService);

        $described = $service->describeEurWithBgnEquivalent(100);

        $this->assertNotNull($described);
        $this->assertSame('100,00', $described['eur']['formatted']);
        $this->assertSame('195,58', $described['bgn']['formatted']);
        $this->assertSame('сто евро', $described['eur']['words']);
        $this->assertSame('сто деветдесет и пет лева и петдесет и осем стотинки', $described['bgn']['words']);
    }
}
