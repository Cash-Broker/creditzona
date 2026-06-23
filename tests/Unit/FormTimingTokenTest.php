<?php

namespace Tests\Unit;

use App\Support\Forms\FormTimingToken;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FormTimingTokenTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_accepts_a_token_filled_after_the_minimum_dwell_time(): void
    {
        $token = FormTimingToken::issue(now()->subSeconds(5)->getTimestampMs());

        $this->assertTrue(FormTimingToken::isValid($token));
    }

    public function test_it_rejects_a_token_submitted_too_fast(): void
    {
        $token = FormTimingToken::issue(now()->getTimestampMs());

        $this->assertFalse(FormTimingToken::isValid($token));
    }

    public function test_it_rejects_a_token_with_a_tampered_timestamp(): void
    {
        $token = FormTimingToken::issue(now()->subSeconds(10)->getTimestampMs());

        [, $signature] = explode('.', $token, 2);
        $tampered = now()->subSeconds(5)->getTimestampMs().'.'.$signature;

        $this->assertFalse(FormTimingToken::isValid($tampered));
    }

    public function test_it_rejects_a_token_with_an_invalid_signature(): void
    {
        $token = now()->subSeconds(10)->getTimestampMs().'.not-a-real-signature';

        $this->assertFalse(FormTimingToken::isValid($token));
    }

    public function test_it_rejects_a_stale_token(): void
    {
        $token = FormTimingToken::issue(now()->subDays(2)->getTimestampMs());

        $this->assertFalse(FormTimingToken::isValid($token));
    }

    public function test_it_rejects_malformed_or_empty_tokens(): void
    {
        $this->assertFalse(FormTimingToken::isValid(null));
        $this->assertFalse(FormTimingToken::isValid(''));
        $this->assertFalse(FormTimingToken::isValid('no-separator'));
        $this->assertFalse(FormTimingToken::isValid('abc.def'));
    }
}
