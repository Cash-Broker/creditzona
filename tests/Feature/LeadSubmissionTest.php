<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
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
            'assigned_user_id' => null,
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

    public function test_submission_accepts_additional_fields_and_guarantors(): void
    {
        $response = $this->postJson('/leads', $this->validPayload([
            'middle_name' => 'Петров',
            'workplace' => 'Тест ООД',
            'job_title' => 'Кредитен консултант',
            'salary' => 2800,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 1,
            'salary_bank' => 'УниКредит Булбанк',
            'guarantors' => [
                [
                    'first_name' => 'Мария',
                    'last_name' => 'Иванова',
                    'phone' => '0888000111',
                    'status' => LeadGuarantor::STATUS_SUITABLE,
                ],
                [
                    'first_name' => 'Георги',
                    'last_name' => 'Петров',
                    'phone' => null,
                    'status' => LeadGuarantor::STATUS_UNSUITABLE,
                ],
            ],
        ]));

        $response->assertOk();

        $lead = Lead::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'middle_name' => 'Петров',
            'workplace' => 'Тест ООД',
            'job_title' => 'Кредитен консултант',
            'salary' => 2800,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 1,
            'salary_bank' => 'УниКредит Булбанк',
        ]);

        $this->assertDatabaseCount('lead_guarantors', 2);

        $this->assertDatabaseHas('lead_guarantors', [
            'lead_id' => $lead->id,
            'first_name' => 'Мария',
            'last_name' => 'Иванова',
            'phone' => '0888000111',
            'status' => LeadGuarantor::STATUS_SUITABLE,
        ]);

        $this->assertDatabaseHas('lead_guarantors', [
            'lead_id' => $lead->id,
            'first_name' => 'Георги',
            'last_name' => 'Петров',
            'phone' => null,
            'status' => LeadGuarantor::STATUS_UNSUITABLE,
        ]);
    }

    public function test_invalid_additional_fields_return_validation_errors(): void
    {
        $response = $this->postJson('/leads', $this->validPayload([
            'salary' => -1,
            'marital_status' => 'unknown',
            'children_under_18' => -2,
            'guarantors' => [
                [
                    'first_name' => 'Мария',
                    'last_name' => 'Иванова',
                    'phone' => '0888000111',
                    'status' => 'pending',
                ],
            ],
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'salary',
                'marital_status',
                'children_under_18',
                'guarantors.0.status',
            ])
            ->assertJsonPath('errors.salary.0', 'Заплатата не може да бъде отрицателна.');

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('lead_guarantors', 0);
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
            ->assertJsonPath('message', 'Вече има подадена заявка с този телефонен номер през последните 14 дни.')
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

    public function test_old_lead_with_same_phone_reuses_historical_assigned_user(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        $historicalOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Lead::query()->insert([
            'credit_type' => 'consumer',
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0888123456',
            'email' => 'petar@example.com',
            'city' => 'София',
            'amount' => 12000,
            'status' => 'new',
            'assigned_user_id' => $historicalOperator->id,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        $response = $this->postJson('/leads', $this->validPayload());

        $response->assertOk();

        $newLead = Lead::query()->latest('id')->firstOrFail();

        $this->assertSame($historicalOperator->id, $newLead->assigned_user_id);

        Carbon::setTestNow();
    }

    public function test_old_lead_without_assigned_user_uses_fallback_primary_assignment_pool(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'renata@creditzona.test',
        ]);

        Lead::query()->insert([
            'credit_type' => 'consumer',
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0888123456',
            'email' => 'petar@example.com',
            'city' => 'София',
            'amount' => 12000,
            'status' => 'new',
            'assigned_user_id' => null,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        $response = $this->postJson('/leads', $this->validPayload());

        $response->assertOk();

        $newLead = Lead::query()->latest('id')->firstOrFail();

        $this->assertSame($anna->id, $newLead->assigned_user_id);

        Carbon::setTestNow();
    }

    public function test_new_lead_without_history_uses_fallback_primary_assignment_pool(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'iskra@creditzona.test',
        ]);

        $response = $this->postJson('/leads', $this->validPayload([
            'phone' => '0888999999',
        ]));

        $response->assertOk();

        $newLead = Lead::query()->latest('id')->firstOrFail();

        $this->assertSame($anna->id, $newLead->assigned_user_id);
    }

    public function test_fallback_assignment_uses_round_robin_between_the_three_primary_operators(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $krasimira = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'krasimira@creditzona.test',
        ]);

        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'renata@creditzona.test',
        ]);

        $phones = [
            '0888000001',
            '0888000002',
            '0888000003',
            '0888000004',
        ];

        foreach ($phones as $phone) {
            $this->postJson('/leads', $this->validPayload([
                'phone' => $phone,
            ]))->assertOk();
        }

        $assignedUserIds = Lead::query()
            ->orderBy('id')
            ->pluck('assigned_user_id')
            ->all();

        $this->assertSame([
            $anna->id,
            $elena->id,
            $krasimira->id,
            $anna->id,
        ], $assignedUserIds);
    }

    public function test_multiple_sequential_new_leads_are_distributed_evenly_with_round_robin(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $krasimira = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'krasimira@creditzona.test',
        ]);

        foreach (range(1, 9) as $index) {
            $this->postJson('/leads', $this->validPayload([
                'phone' => sprintf('08880000%02d', $index),
            ]))->assertOk();
        }

        $distribution = Lead::query()
            ->selectRaw('assigned_user_id, COUNT(*) as aggregate')
            ->groupBy('assigned_user_id')
            ->pluck('aggregate', 'assigned_user_id');

        $this->assertSame(3, $distribution[$anna->id]);
        $this->assertSame(3, $distribution[$elena->id]);
        $this->assertSame(3, $distribution[$krasimira->id]);
    }

    public function test_submission_rejects_latin_letters_in_public_text_fields(): void
    {
        $response = $this->postJson('/leads', $this->validPayload([
            'first_name' => 'Ivan',
            'last_name' => 'Ivanov',
            'city' => 'Plovdiv',
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'city',
            ])
            ->assertJsonPath('errors.first_name.0', 'Името трябва да съдържа само букви на кирилица.')
            ->assertJsonPath('errors.last_name.0', 'Фамилията трябва да съдържа само букви на кирилица.')
            ->assertJsonPath('errors.city.0', 'Градът не може да съдържа латински букви.');
    }

    public function test_submission_rejects_latin_letters_in_additional_fields_and_guarantors(): void
    {
        $response = $this->postJson('/leads', $this->validPayload([
            'credit_type' => 'mortgage',
            'property_type' => 'house',
            'property_location' => 'Center 5',
            'workplace' => 'Office 1',
            'job_title' => 'Manager',
            'salary_bank' => 'Bank',
            'guarantors' => [
                [
                    'first_name' => 'Maria',
                    'last_name' => 'Petrova',
                    'phone' => '0888000111',
                    'status' => LeadGuarantor::STATUS_SUITABLE,
                ],
            ],
        ]));

        $errors = $response->json('errors');

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'workplace',
                'job_title',
                'salary_bank',
                'property_location',
                'guarantors.0.first_name',
                'guarantors.0.last_name',
            ])
            ->assertJsonPath('errors.workplace.0', 'Местоработата не може да съдържа латински букви.')
            ->assertJsonPath('errors.job_title.0', 'Длъжността не може да съдържа латински букви.')
            ->assertJsonPath('errors.salary_bank.0', 'Банката за заплата не може да съдържа латински букви.')
            ->assertJsonPath('errors.property_location.0', 'Местонахождението на имота не може да съдържа латински букви.');

        $this->assertSame(
            'Името на поръчителя трябва да съдържа само букви на кирилица.',
            $errors['guarantors.0.first_name'][0] ?? null,
        );
        $this->assertSame(
            'Фамилията на поръчителя трябва да съдържа само букви на кирилица.',
            $errors['guarantors.0.last_name'][0] ?? null,
        );

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('lead_guarantors', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => 'consumer',
            'first_name' => 'Иван',
            'middle_name' => null,
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'workplace' => null,
            'job_title' => null,
            'salary' => null,
            'marital_status' => null,
            'children_under_18' => null,
            'salary_bank' => null,
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'guarantors' => [],
            'source' => 'landing-page',
            'utm_source' => 'google',
            'utm_campaign' => 'spring-campaign',
            'utm_medium' => 'cpc',
            'gclid' => 'test-gclid',
        ], $overrides);
    }
}
