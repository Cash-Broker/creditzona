<?php

namespace Tests\Unit;

use App\Services\Contracts\BulgarianNumberToWordsService;
use PHPUnit\Framework\TestCase;

class BulgarianNumberToWordsServiceTest extends TestCase
{
    public function test_it_spells_out_common_numbers_in_bulgarian(): void
    {
        $service = new BulgarianNumberToWordsService;

        $this->assertSame('двадесет и една', $service->spellOut(21, BulgarianNumberToWordsService::GENDER_FEMININE));
        $this->assertSame('сто двадесет и три', $service->spellOut(123));
        $this->assertSame('хиляда и едно', $service->spellOut(1001, BulgarianNumberToWordsService::GENDER_NEUTER));
    }

    public function test_it_spells_out_money_amounts(): void
    {
        $service = new BulgarianNumberToWordsService;

        $this->assertSame(
            'два лева и петдесет стотинки',
            $service->spellOutMoney(2.50, 'лев', 'лева'),
        );

        $this->assertSame(
            'едно евро и един цент',
            $service->spellOutMoney(
                1.01,
                'евро',
                'евро',
                BulgarianNumberToWordsService::GENDER_NEUTER,
                'цент',
                'цента',
                BulgarianNumberToWordsService::GENDER_MASCULINE,
            ),
        );
    }
}
