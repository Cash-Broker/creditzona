<?php

namespace Tests\Unit;

use App\Rules\ValidEgn;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidEgnTest extends TestCase
{
    public function test_valid_egn_passes(): void
    {
        $this->assertPasses('7523169263'); // 1975-03-16
        $this->assertPasses('7501010010'); // 1975-01-01
        $this->assertPasses('0041010018'); // 2000-01-01 (month 41 = January 2000s)
    }

    public function test_invalid_checksum_fails(): void
    {
        $this->assertFails('7523169260'); // wrong last digit
        $this->assertFails('7501010011'); // wrong checksum
    }

    public function test_invalid_date_fails(): void
    {
        $this->assertFails('7513010010'); // month 13 is invalid
        $this->assertFails('7501320010'); // day 32 is invalid
        $this->assertFails('7502300010'); // Feb 30 doesn't exist
    }

    public function test_non_digits_fail(): void
    {
        $this->assertFails('750101001a');
        $this->assertFails('abcdefghij');
        $this->assertFails('750101-001');
    }

    public function test_wrong_length_fails(): void
    {
        $this->assertFails('123456789');   // 9 digits
        $this->assertFails('12345678901'); // 11 digits
        $this->assertFails('12345');
    }

    public function test_null_and_empty_pass_because_nullable(): void
    {
        // ValidEgn returns early for null/empty — other rules handle required
        $validator = Validator::make(['egn' => null], ['egn' => ['nullable', new ValidEgn]]);
        $this->assertTrue($validator->passes());

        $validator = Validator::make(['egn' => ''], ['egn' => ['nullable', new ValidEgn]]);
        $this->assertTrue($validator->passes());
    }

    private function assertPasses(string $egn): void
    {
        $validator = Validator::make(['egn' => $egn], ['egn' => [new ValidEgn]]);
        $this->assertTrue($validator->passes(), "Expected EGN '{$egn}' to pass but it failed.");
    }

    private function assertFails(string $egn): void
    {
        $validator = Validator::make(['egn' => $egn], ['egn' => [new ValidEgn]]);
        $this->assertTrue($validator->fails(), "Expected EGN '{$egn}' to fail but it passed.");
    }
}
