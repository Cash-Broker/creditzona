<?php

namespace Tests\Unit;

use App\Rules\CyrillicText;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CyrillicTextTest extends TestCase
{
    public function test_letters_only_rule_accepts_cyrillic_names_and_rejects_other_characters(): void
    {
        $validValidator = Validator::make(
            ['first_name' => 'Мария-Анна'],
            ['first_name' => [CyrillicText::lettersOnly('Името')]],
        );

        $invalidValidator = Validator::make(
            ['first_name' => 'Maria1'],
            ['first_name' => [CyrillicText::lettersOnly('Името')]],
        );

        $this->assertTrue($validValidator->passes());
        $this->assertFalse($invalidValidator->passes());
        $this->assertSame(
            'Името трябва да съдържа само букви на кирилица.',
            $invalidValidator->errors()->first('first_name'),
        );
    }

    public function test_without_latin_rule_allows_digits_and_symbols_but_rejects_latin_letters(): void
    {
        $validValidator = Validator::make(
            ['property_location' => 'ж.к. Люлин 5, бл. 12'],
            ['property_location' => [CyrillicText::withoutLatin('Местоположението на имота')]],
        );

        $invalidValidator = Validator::make(
            ['property_location' => 'Center 5'],
            ['property_location' => [CyrillicText::withoutLatin('Местоположението на имота')]],
        );

        $this->assertTrue($validValidator->passes());
        $this->assertFalse($invalidValidator->passes());
        $this->assertSame(
            'Местоположението на имота не може да съдържа латински букви.',
            $invalidValidator->errors()->first('property_location'),
        );
    }
}
