<?php

namespace Tests\Feature;

use App\Models\Faq;
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
        $response->assertSee('lead-personal-data-consent-v1.pdf', false);
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

    public function test_faq_schema_escapes_dangerous_script_sequences(): void
    {
        Faq::query()->create([
            'question' => 'Безопасно ли е?',
            'answer' => '</script><script>alert("x")</script>',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $response = $this->get('/faq');

        $response->assertOk();
        $response->assertDontSee('</script><script>alert("x")</script>', false);
        $response->assertSee('\u003C\/script\u003E\u003Cscript\u003Ealert(', false);
    }
}
