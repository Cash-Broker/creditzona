<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use App\Support\Lead\ClientHistoryLookup;
use App\Support\Notes\NoteHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadClientHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_surfaces_previous_guarantors_and_notes_for_a_newly_assigned_employee(): void
    {
        $previousOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $newOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $previousLead = Lead::query()->create($this->leadAttributes([
            'phone' => '0888123456',
            'assigned_user_id' => $previousOperator->id,
            'internal_notes' => NoteHistory::append(
                null,
                'Клиентът кандидатства през март.',
                $previousOperator->name,
                $previousOperator->id,
            ),
        ]));

        LeadGuarantor::query()->create([
            'lead_id' => $previousLead->id,
            'first_name' => 'Гошо',
            'last_name' => 'Гошев',
            'phone' => '0877000000',
            'status' => LeadGuarantor::STATUS_UNSUITABLE,
            'internal_notes' => NoteHistory::append(
                null,
                'Негоден — нисък доход.',
                $previousOperator->name,
                $previousOperator->id,
            ),
        ]);

        $currentLead = Lead::query()->create($this->leadAttributes([
            'phone' => '0888123456',
            'assigned_user_id' => $newOperator->id,
        ]));

        $this->actingAs($newOperator);

        Livewire::test(ViewLead::class, ['record' => (string) $currentLead->getKey()])
            ->assertSee('Предишни заявки на клиента')
            ->assertSee('Гошо Гошев')
            ->assertSee('Негоден')
            ->assertSee('Негоден — нисък доход.')
            ->assertSee('Клиентът кандидатства през март.');
    }

    public function test_view_hides_history_section_when_client_has_no_previous_submissions(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadAttributes([
            'phone' => '0899111111',
            'assigned_user_id' => $operator->id,
        ]));

        $this->actingAs($operator);

        Livewire::test(ViewLead::class, ['record' => (string) $lead->getKey()])
            ->assertDontSee('Предишни заявки на клиента');
    }

    public function test_previous_guarantor_egn_is_masked_in_history(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $previousLead = Lead::query()->create($this->leadAttributes([
            'phone' => '0888222333',
            'assigned_user_id' => $operator->id,
        ]));

        LeadGuarantor::query()->create([
            'lead_id' => $previousLead->id,
            'first_name' => 'Гошо',
            'last_name' => 'Гошев',
            'phone' => '0877000000',
            'egn' => '9001011234',
            'status' => LeadGuarantor::STATUS_SUITABLE,
        ]);

        $currentLead = Lead::query()->create($this->leadAttributes([
            'phone' => '0888222333',
            'assigned_user_id' => $operator->id,
        ]));

        $this->actingAs($admin);

        Livewire::test(ViewLead::class, ['record' => (string) $currentLead->getKey()])
            ->assertSee('******1234')
            ->assertDontSee('9001011234');
    }

    public function test_lookup_matches_previous_submissions_by_phone_excluding_current(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $first = Lead::query()->create($this->leadAttributes([
            'phone' => '0888123456',
            'assigned_user_id' => $operator->id,
        ]));

        $second = Lead::query()->create($this->leadAttributes([
            'phone' => '0888123456',
            'assigned_user_id' => $operator->id,
        ]));

        $current = Lead::query()->create($this->leadAttributes([
            'phone' => '0888123456',
            'assigned_user_id' => $operator->id,
        ]));

        $unrelated = Lead::query()->create($this->leadAttributes([
            'phone' => '0899000000',
            'assigned_user_id' => $operator->id,
        ]));

        $previous = ClientHistoryLookup::previousSubmissions($current);

        $this->assertCount(2, $previous);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $previous->pluck('id')->all());
        $this->assertTrue(ClientHistoryLookup::hasPreviousSubmissions($current));
        $this->assertFalse(ClientHistoryLookup::hasPreviousSubmissions($unrelated));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function leadAttributes(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'egn' => '9001010000',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
        ], $overrides);
    }
}
