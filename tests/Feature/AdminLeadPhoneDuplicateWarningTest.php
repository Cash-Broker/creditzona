<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\Pages\EditLead;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use App\Support\Phone\LeadPhoneOwnerLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLeadPhoneDuplicateWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_lead_with_phone_matching_another_lead_applicant(): void
    {
        [$admin, $operator] = $this->makeAdminAndOperator();

        Lead::query()->create($this->leadAttributes([
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0888123456',
            'assigned_user_id' => $operator->id,
        ]));

        $duplicateLead = Lead::query()->create($this->leadAttributes([
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'assigned_user_id' => $operator->id,
        ]));

        $this->actingAs($admin);

        Livewire::test(EditLead::class, [
            'record' => (string) $duplicateLead->getKey(),
        ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(2, Lead::query()->where('normalized_phone', '0888123456')->count());
    }

    public function test_admin_can_save_lead_with_phone_matching_another_lead_guarantor(): void
    {
        [$admin, $operator] = $this->makeAdminAndOperator();

        $existing = Lead::query()->create($this->leadAttributes([
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0877777777',
            'assigned_user_id' => $operator->id,
        ]));

        LeadGuarantor::query()->create([
            'lead_id' => $existing->id,
            'first_name' => 'Гошо',
            'last_name' => 'Гошев',
            'phone' => '0888123456',
        ]);

        $duplicateLead = Lead::query()->create($this->leadAttributes([
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'assigned_user_id' => $operator->id,
        ]));

        $this->actingAs($admin);

        Livewire::test(EditLead::class, [
            'record' => (string) $duplicateLead->getKey(),
        ])
            ->assertSee('Този телефон вече е използван за поръчител')
            ->assertSee('Този телефон вече е въведен за')
            ->assertSee('Гошо Гошев')
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_lookup_returns_applicants_and_guarantors_excluding_current_records(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $otherApplicant = Lead::query()->create($this->leadAttributes([
            'first_name' => 'Петър',
            'last_name' => 'Петров',
            'phone' => '0888123456',
            'assigned_user_id' => $operator->id,
        ]));

        $currentLead = Lead::query()->create($this->leadAttributes([
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '0888123456',
            'assigned_user_id' => $operator->id,
        ]));

        $hostLead = Lead::query()->create($this->leadAttributes([
            'first_name' => 'Стоян',
            'last_name' => 'Стоянов',
            'phone' => '0899000000',
            'assigned_user_id' => $operator->id,
        ]));

        LeadGuarantor::query()->create([
            'lead_id' => $hostLead->id,
            'first_name' => 'Гошо',
            'last_name' => 'Гошев',
            'phone' => '0888123456',
        ]);

        $owners = LeadPhoneOwnerLookup::findOwners('+359888123456', $currentLead->id, null);

        $this->assertCount(2, $owners);

        $names = $owners->pluck('name')->all();
        $this->assertContains('Петър Петров', $names);
        $this->assertContains('Гошо Гошев', $names);
        $this->assertNotContains('Иван Иванов', $names);
        $this->assertSame($otherApplicant->id, $owners->firstWhere('role', LeadPhoneOwnerLookup::ROLE_APPLICANT)['lead_id']);
        $this->assertSame($hostLead->id, $owners->firstWhere('role', LeadPhoneOwnerLookup::ROLE_GUARANTOR)['lead_id']);
    }

    public function test_lookup_returns_empty_collection_when_phone_blank(): void
    {
        $this->assertTrue(LeadPhoneOwnerLookup::findOwners(null)->isEmpty());
        $this->assertTrue(LeadPhoneOwnerLookup::findOwners('')->isEmpty());
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function makeAdminAndOperator(): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        return [$admin, $operator];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function leadAttributes(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'egn' => '9001010000',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
        ], $overrides);
    }
}
