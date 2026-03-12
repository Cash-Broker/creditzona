<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeadSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_consumer_lead_submission(): void
    {
        $response = $this->postJson('/leads', $this->validPayload());

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Благодарим! Ще се свържем с вас до 48ч.',
            ]);

        $this->assertDatabaseHas('leads', [
            'credit_type' => 'consumer',
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'status' => 'new',
            'source' => 'landing-page',
            'utm_source' => 'google',
            'utm_campaign' => 'spring-campaign',
            'utm_medium' => 'cpc',
            'gclid' => 'test-gclid',
        ]);
    }

    public function test_successful_mortgage_lead_submission(): void
    {
        $response = $this->postJson('/leads', $this->validPayload([
            'credit_type' => 'mortgage',
            'property_type' => 'house',
            'property_location' => 'София',
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Благодарим! Ще се свържем с вас до 48ч.',
            ]);

        $this->assertDatabaseHas('leads', [
            'credit_type' => 'mortgage',
            'property_type' => 'house',
            'property_location' => 'София',
        ]);
    }

    public function test_mortgage_without_property_type_returns_validation_error(): void
    {
        $response = $this->postJson('/leads', $this->validPayload([
            'credit_type' => 'mortgage',
            'property_type' => null,
            'property_location' => 'София',
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_type'])
            ->assertJsonPath('errors.property_type.0', 'Моля, изберете вид на имота.');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_missing_required_fields_return_validation_errors(): void
    {
        $response = $this->postJson('/leads', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'credit_type',
                'first_name',
                'last_name',
                'phone',
                'email',
                'city',
                'amount',
            ])
            ->assertJsonPath('errors.first_name.0', 'Моля, въведете вашето име.');
    }

    public function test_amount_out_of_range_returns_validation_error(): void
    {
        $response = $this->postJson('/leads', $this->validPayload([
            'amount' => 60000,
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount'])
            ->assertJsonPath('errors.amount.0', 'Сумата не може да бъде повече от 50000.');
    }

    public function test_recent_lead_with_same_phone_returns_validation_error(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        Lead::query()->insert([
            'credit_type' => 'consumer',
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0888123456',
            'email' => 'petar@example.com',
            'city' => 'София',
            'amount' => 12000,
            'status' => 'new',
            'created_at' => now()->subDays(13),
            'updated_at' => now()->subDays(13),
        ]);

        $response = $this->postJson('/leads', $this->validPayload());

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone'])
            ->assertJsonPath(
                'errors.phone.0',
                'Вече има подадена заявка с този телефонен номер през последните 14 дни.',
            );

        $this->assertDatabaseCount('leads', 1);

        Carbon::setTestNow();
    }

    public function test_old_lead_with_same_phone_allows_new_submission(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        Lead::query()->insert([
            'credit_type' => 'consumer',
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0888123456',
            'email' => 'petar@example.com',
            'city' => 'София',
            'amount' => 12000,
            'status' => 'new',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        $response = $this->postJson('/leads', $this->validPayload());

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Благодарим! Ще се свържем с вас до 48ч.',
            ]);

        $this->assertDatabaseCount('leads', 2);

        Carbon::setTestNow();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => 'consumer',
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'source' => 'landing-page',
            'utm_source' => 'google',
            'utm_campaign' => 'spring-campaign',
            'utm_medium' => 'cpc',
            'gclid' => 'test-gclid',
        ], $overrides);
    }
}
