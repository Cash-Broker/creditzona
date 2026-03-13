<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_core_seo_tags(): void
    {
        $response = $this->get('/');
        $baseUrl = rtrim((string) config('app.url'), '/');

        $response->assertOk();
        $response->assertSee(
            '<title>Кредитен консултант и консултация за кредити в Пловдив | Кредит Зона</title>',
            false,
        );
        $response->assertSee(
            '<meta name="description" content="Получете ясна консултация за кредити в Пловдив с анализ на ситуацията, реалистични насоки и ясен план за следващите стъпки.">',
            false,
        );
        $response->assertSee(
            '<link rel="canonical" href="'.$baseUrl.'">',
            false,
        );
        $response->assertSee(
            '<meta name="robots" content="index,follow">',
            false,
        );
        $response->assertSee(
            '"@type": "FinancialService"',
            false,
        );
    }

    public function test_legal_page_is_marked_noindex(): void
    {
        $response = $this->get('/politika-za-poveritelnost');

        $response->assertOk();
        $response->assertSee(
            '<meta name="robots" content="noindex,follow">',
            false,
        );
    }
}
