<?php

namespace Tests\Feature;

use App\Mail\LeadSubmittedConfirmation;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadPrivacyConsentPdfService;
use App\Services\LeadService;
use App\Support\Forms\FormTimingToken;
use App\Support\Lead\ClientHistoryLookup;
use App\Support\Notes\NoteHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class LeadClientDataBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_second_application_backfills_personal_data_from_previous_lead(): void
    {
        $operator = $this->createOperator();

        $this->createPreviousLead([
            'assigned_user_id' => $operator->id,
            'internal_notes' => NoteHistory::append(null, 'Проверен клиент.', $operator->name, $operator->id),
        ])->guarantors()->create([
            'first_name' => 'Мария',
            'last_name' => 'Иванова',
            'phone' => '0888999999',
            'status' => 'suitable',
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->assertSame('8501010000', $lead->egn);
        $this->assertSame('Петров', $lead->middle_name);
        $this->assertSame('Строй ЕООД', $lead->workplace);
        $this->assertSame('Техник', $lead->job_title);
        $this->assertSame(1800, $lead->salary);
        $this->assertSame(Lead::MARITAL_STATUS_MARRIED, $lead->marital_status);
        $this->assertSame(2, $lead->children_under_18);
        $this->assertSame('ОББ', $lead->salary_bank);
        $this->assertSame('ДСК', $lead->credit_bank);
        $this->assertSame('Апартамент в Пловдив', $lead->movable_immovable_property);

        $this->assertSame(0, $lead->guarantors()->count());
        $this->assertNull($lead->internal_notes);
        $this->assertNull($lead->documents);
    }

    public function test_current_submission_values_take_precedence_over_history(): void
    {
        $operator = $this->createOperator();

        $this->createPreviousLead(['assigned_user_id' => $operator->id]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
            'city' => 'Варна',
            'email' => 'nov@example.com',
            'workplace' => 'Ново ЕООД',
            'salary' => 2500,
        ]));

        $this->assertSame('Варна', $lead->city);
        $this->assertSame('nov@example.com', $lead->email);
        $this->assertSame('Ново ЕООД', $lead->workplace);
        $this->assertSame(2500, $lead->salary);
        $this->assertSame('8501010000', $lead->egn);
    }

    public function test_backfill_takes_newest_value_per_field_and_falls_back_to_older_leads(): void
    {
        $operator = $this->createOperator();

        $this->createPreviousLead([
            'assigned_user_id' => $operator->id,
            'workplace' => 'Старо АД',
        ]);

        $this->createPreviousLead([
            'assigned_user_id' => $operator->id,
            'egn' => null,
            'workplace' => 'Ново ЕООД',
            'salary' => null,
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->assertSame('Ново ЕООД', $lead->workplace);
        $this->assertSame('8501010000', $lead->egn);
        $this->assertSame(1800, $lead->salary);
    }

    public function test_no_backfill_without_previous_submissions(): void
    {
        $operator = $this->createOperator();

        $this->createPreviousLead([
            'assigned_user_id' => $operator->id,
            'phone' => '0888777777',
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->assertNull($lead->egn);
        $this->assertNull($lead->middle_name);
        $this->assertNull($lead->workplace);
        $this->assertNull($lead->salary);
        $this->assertNull($lead->credit_bank);
    }

    public function test_backfill_matches_legacy_rows_without_normalized_phone(): void
    {
        $operator = $this->createOperator();

        $previousLead = $this->createPreviousLead(['assigned_user_id' => $operator->id]);

        DB::table('leads')
            ->where('id', $previousLead->id)
            ->update(['normalized_phone' => null]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
            'phone' => '+359 888 123 456',
        ]));

        $this->assertSame('8501010000', $lead->egn);
        $this->assertSame('Строй ЕООД', $lead->workplace);
    }

    public function test_guarantor_comes_from_new_submission_only(): void
    {
        $operator = $this->createOperator();

        $this->createPreviousLead([
            'assigned_user_id' => $operator->id,
        ])->guarantors()->create([
            'first_name' => 'Мария',
            'last_name' => 'Иванова',
            'phone' => '0888999999',
            'status' => 'suitable',
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'guarantors' => [
                [
                    'first_name' => 'Петър',
                    'last_name' => 'Петров',
                    'phone' => '0888555555',
                    'status' => 'suitable',
                ],
            ],
        ]));

        $this->assertSame(1, $lead->guarantors()->count());
        $this->assertSame('Петър', $lead->guarantors()->first()->first_name);
    }

    public function test_privacy_consent_snapshot_reflects_submitted_data_only(): void
    {
        $operator = $this->createOperator();

        $this->createPreviousLead(['assigned_user_id' => $operator->id]);

        $egnAtSnapshotTime = 'not-captured';
        $middleNameAtSnapshotTime = 'not-captured';

        $this->mock(
            LeadPrivacyConsentPdfService::class,
            function (MockInterface $mock) use (&$egnAtSnapshotTime, &$middleNameAtSnapshotTime): void {
                $mock->shouldReceive('storeSnapshot')
                    ->once()
                    ->andReturnUsing(function (Lead $lead) use (&$egnAtSnapshotTime, &$middleNameAtSnapshotTime): array {
                        $egnAtSnapshotTime = $lead->egn;
                        $middleNameAtSnapshotTime = $lead->middle_name;

                        return [
                            'path' => 'lead-consents/testing/snapshot.pdf',
                            'name' => Lead::getPrivacyConsentDocumentName(),
                        ];
                    });
            },
        );

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
            'privacy_consent' => true,
        ]));

        $this->assertNull($egnAtSnapshotTime);
        $this->assertNull($middleNameAtSnapshotTime);
        $this->assertSame('8501010000', $lead->egn);
        $this->assertSame('Петров', $lead->middle_name);
    }

    public function test_confirmation_email_reflects_submitted_data_only(): void
    {
        Mail::fake();

        $operator = $this->createOperator();

        $this->createPreviousLead(['assigned_user_id' => $operator->id]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->assertSame('Петров', $lead->middle_name);

        Mail::assertSent(
            LeadSubmittedConfirmation::class,
            fn (LeadSubmittedConfirmation $mail): bool => $mail->lead->middle_name === null
                && $mail->lead->egn === null,
        );
    }

    public function test_no_backfill_when_names_do_not_match_previous_phone_owner(): void
    {
        $operator = $this->createOperator();

        $this->createPreviousLead(['assigned_user_id' => $operator->id]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
            'first_name' => 'Георги',
            'last_name' => 'Георгиев',
        ]));

        $this->assertNull($lead->egn);
        $this->assertNull($lead->middle_name);
        $this->assertNull($lead->workplace);
        $this->assertNull($lead->salary);
        $this->assertNull($lead->credit_bank);
    }

    public function test_undecryptable_egn_in_history_does_not_break_intake(): void
    {
        $operator = $this->createOperator();

        $previousLead = $this->createPreviousLead(['assigned_user_id' => $operator->id]);

        DB::table('leads')
            ->where('id', $previousLead->id)
            ->update(['egn' => 'not-a-valid-ciphertext']);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->assertNull($lead->egn);
        $this->assertSame('Строй ЕООД', $lead->workplace);
        $this->assertSame('Петров', $lead->middle_name);
    }

    public function test_contact_message_leads_do_not_contribute_parsed_middle_name(): void
    {
        $operator = $this->createOperator();

        $this->createPreviousLead([
            'assigned_user_id' => $operator->id,
            'egn' => null,
        ]);

        $this->createPreviousLead([
            'assigned_user_id' => $operator->id,
            'middle_name' => 'П.',
            'workplace' => null,
            'source' => Lead::SOURCE_CONTACT_MESSAGE,
        ]);

        $lead = app(LeadService::class)->createLead($this->leadData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->assertSame('Петров', $lead->middle_name);
        $this->assertSame('8501010000', $lead->egn);
    }

    public function test_backfill_applies_through_public_lead_endpoint(): void
    {
        Carbon::setTestNow('2026-07-01 10:00:00');

        $operator = $this->createOperator();
        $this->createPreviousLead(['assigned_user_id' => $operator->id]);

        Carbon::setTestNow('2026-07-20 10:00:00');

        $response = $this->postJson('/leads', $this->publicPayload());

        $response->assertOk();

        $lead = Lead::query()->latest('id')->first();

        $this->assertSame('8501010000', $lead->egn);
        $this->assertSame('Петров', $lead->middle_name);
        $this->assertSame('Строй ЕООД', $lead->workplace);

        Carbon::setTestNow();
    }

    public function test_public_payload_cannot_inject_backfill_only_fields(): void
    {
        $response = $this->postJson('/leads', $this->publicPayload([
            'phone' => '0888777666',
            'egn' => '9001010000',
            'credit_bank' => 'Инжектирана Банка',
            'movable_immovable_property' => 'Инжектирано имущество',
        ]));

        $response->assertOk();

        $lead = Lead::query()->latest('id')->first();

        $this->assertNull($lead->egn);
        $this->assertNull($lead->credit_bank);
        $this->assertNull($lead->movable_immovable_property);
    }

    public function test_personal_data_defaults_are_empty_for_blank_phone_or_names(): void
    {
        $this->assertSame([], ClientHistoryLookup::personalDataDefaults(null, 'Иван', 'Иванов'));
        $this->assertSame([], ClientHistoryLookup::personalDataDefaults('   ', 'Иван', 'Иванов'));
        $this->assertSame([], ClientHistoryLookup::personalDataDefaults('0888123456', null, 'Иванов'));
        $this->assertSame([], ClientHistoryLookup::personalDataDefaults('0888123456', 'Иван', '   '));
    }

    private function createOperator(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);
    }

    private function createPreviousLead(array $overrides = []): Lead
    {
        return Lead::query()->create(array_merge([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'middle_name' => 'Петров',
            'last_name' => 'Иванов',
            'egn' => '8501010000',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'workplace' => 'Строй ЕООД',
            'job_title' => 'Техник',
            'salary' => 1800,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 2,
            'salary_bank' => 'ОББ',
            'credit_bank' => 'ДСК',
            'movable_immovable_property' => 'Апартамент в Пловдив',
            'amount' => 6000,
            'status' => 'new',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function publicPayload(array $overrides = []): array
    {
        return array_merge($this->leadData([
            'privacy_consent' => true,
        ]), [
            'website' => '',
            'form_timing_token' => FormTimingToken::issue(now()->subSeconds(5)->getTimestampMs()),
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
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
            'additional_user_id' => null,
            'privacy_consent' => false,
        ], $overrides);
    }
}
