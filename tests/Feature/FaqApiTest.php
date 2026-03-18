<?php

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_api_returns_only_public_fields_for_published_items(): void
    {
        Faq::query()->create([
            'question' => 'Какво е рефинансиране?',
            'answer' => 'Рефинансирането заменя текущ кредит с нов.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Faq::query()->create([
            'question' => 'Скрит въпрос',
            'answer' => 'Не трябва да се вижда.',
            'sort_order' => 2,
            'is_published' => false,
        ]);

        $this->getJson('/api/faqs')
            ->assertOk()
            ->assertExactJson([
                [
                    'question' => 'Какво е рефинансиране?',
                    'answer' => 'Рефинансирането заменя текущ кредит с нов.',
                ],
            ]);
    }
}
