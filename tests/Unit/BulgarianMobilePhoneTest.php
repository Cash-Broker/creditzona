<?php

namespace Tests\Unit;

use App\Rules\BulgarianMobilePhone;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BulgarianMobilePhoneTest extends TestCase
{
    public function test_it_accepts_valid_bulgarian_mobile_numbers(): void
    {
        $validNumbers = [
            'national with leading zero' => '0888123456',
            'national with spaces' => '0888 123 456',
            'international with plus' => '+359888123456',
            'international with spaces' => '+359 888 123 456',
            'international without plus' => '359888123456',
            'double zero international' => '00359888123456',
            'international keeping the zero' => '+3590888123456',
            'starts with nine' => '0987654321',
        ];

        foreach ($validNumbers as $label => $value) {
            $validator = Validator::make(
                ['phone' => $value],
                ['phone' => [new BulgarianMobilePhone]],
            );

            $this->assertTrue($validator->passes(), "Expected {$label} ({$value}) to be accepted.");
        }
    }

    public function test_it_rejects_invalid_or_non_mobile_numbers(): void
    {
        $invalidNumbers = [
            'foreign garbage' => '4368860324616',
            'too short' => '088812345',
            'too long' => '08881234567',
            'landline sofia' => '029876543',
            'landline area code start' => '+359321234567',
            'letters' => 'телефон',
        ];

        foreach ($invalidNumbers as $label => $value) {
            $validator = Validator::make(
                ['phone' => $value],
                ['phone' => [new BulgarianMobilePhone]],
            );

            $this->assertFalse($validator->passes(), "Expected {$label} to be rejected.");
            $this->assertSame(
                'Моля, въведете валиден мобилен телефонен номер.',
                $validator->errors()->first('phone'),
                "Wrong message for {$label}.",
            );
        }
    }
}
