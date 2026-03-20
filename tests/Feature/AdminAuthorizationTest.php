<?php

namespace Tests\Feature;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use App\Filament\Resources\AttachedLeads\Pages\EditAttachedLead;
use App\Filament\Resources\AttachedLeads\Pages\ViewAttachedLead;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\User;
use App\Policies\LeadPolicy;
use App\Services\LeadService;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_staff_roles_can_access_admin_panel_and_lead_records(): void
    {
        $renata = User::factory()->make([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $primaryOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $additionalOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $unrelatedOperator = User::factory()->make([
            'role' => User::ROLE_OPERATOR,
            'email' => 'iskra@creditzona.test',
        ]);

        $nonStaff = User::factory()->make([
            'role' => 'customer',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'assigned_user_id' => $primaryOperator->id,
            'additional_user_id' => $additionalOperator->id,
        ]));
        $policy = new LeadPolicy;
        $adminPanel = (new Panel)->id('admin');

        $this->assertTrue($renata->canAccessPanel($adminPanel));
        $this->assertTrue($primaryOperator->canAccessPanel($adminPanel));
        $this->assertFalse($nonStaff->canAccessPanel($adminPanel));

        $this->assertTrue($policy->viewAny($renata));
        $this->assertTrue($policy->view($primaryOperator, $lead));
        $this->assertTrue($policy->view($additionalOperator, $lead));
        $this->assertTrue($policy->update($renata, $lead));
        $this->assertFalse($policy->view($unrelatedOperator, $lead));

        $this->assertFalse($policy->viewAny($nonStaff));
        $this->assertFalse($policy->view($nonStaff, $lead));
        $this->assertFalse($policy->update($nonStaff, $lead));
    }

    public function test_lead_resource_query_is_scoped_to_primary_and_additional_operator_assignments(): void
    {
        $renata = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $iskra = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'iskra@creditzona.test',
        ]);

        $assignedLead = Lead::query()->create($this->leadData([
            'phone' => '0888000001',
            'assigned_user_id' => $anna->id,
        ]));

        $collaboratingLead = Lead::query()->create($this->leadData([
            'phone' => '0888000002',
            'assigned_user_id' => $elena->id,
            'additional_user_id' => $anna->id,
        ]));

        $unrelatedLead = Lead::query()->create($this->leadData([
            'phone' => '0888000003',
            'assigned_user_id' => $iskra->id,
        ]));

        $this->actingAs($anna);

        $operatorVisibleLeadIds = LeadResource::getEloquentQuery()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([
            $assignedLead->id,
            $collaboratingLead->id,
        ], $operatorVisibleLeadIds);

        $this->actingAs($renata);

        $adminVisibleLeadIds = LeadResource::getEloquentQuery()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([
            $assignedLead->id,
            $collaboratingLead->id,
            $unrelatedLead->id,
        ], $adminVisibleLeadIds);
    }

    public function test_attached_lead_resource_query_is_scoped_only_to_additional_assignments_for_current_user(): void
    {
        $renata = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $attachedToAnna = Lead::query()->create($this->leadData([
            'phone' => '0888111111',
            'assigned_user_id' => $elena->id,
            'additional_user_id' => $anna->id,
        ]));

        Lead::query()->create($this->leadData([
            'phone' => '0888222222',
            'email' => 'second@example.com',
            'assigned_user_id' => $anna->id,
            'additional_user_id' => null,
        ]));

        $attachedToRenata = Lead::query()->create($this->leadData([
            'phone' => '0888333333',
            'email' => 'third@example.com',
            'assigned_user_id' => $anna->id,
            'additional_user_id' => $renata->id,
        ]));

        $this->actingAs($anna);

        $operatorAttachedLeadIds = AttachedLeadResource::getEloquentQuery()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([
            $attachedToAnna->id,
        ], $operatorAttachedLeadIds);

        $this->actingAs($renata);

        $adminAttachedLeadIds = AttachedLeadResource::getEloquentQuery()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([
            $attachedToRenata->id,
        ], $adminAttachedLeadIds);
    }

    public function test_returned_attached_lead_disappears_from_attached_resource_query(): void
    {
        $renata = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'phone' => '0888444444',
            'email' => 'returned@example.com',
            'assigned_user_id' => $anna->id,
            'additional_user_id' => $renata->id,
        ]));

        $this->actingAs($renata);

        $this->assertSame([$lead->id], AttachedLeadResource::getEloquentQuery()->pluck('id')->all());

        app(LeadService::class)->returnAttachedLeadToPrimary($lead, $renata);

        $lead->refresh();

        $this->assertNull($lead->additional_user_id);
        $this->assertSame([], AttachedLeadResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_attached_lead_pages_expose_edit_then_return_actions_for_admin(): void
    {
        $renata = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $lead = Lead::query()->create($this->leadData([
            'phone' => '0888555555',
            'email' => 'actions@example.com',
            'assigned_user_id' => $anna->id,
            'additional_user_id' => $renata->id,
        ]));

        $this->actingAs($renata);

        Livewire::test(ViewAttachedLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->assertActionExists('edit');

        Livewire::test(EditAttachedLead::class, [
            'record' => (string) $lead->getKey(),
        ])
            ->assertActionExists('return_to_primary');
    }

    /**
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => 'consumer',
            'first_name' => 'Ivan',
            'last_name' => 'Ivanov',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Plovdiv',
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'status' => 'new',
            'assigned_user_id' => null,
            'additional_user_id' => null,
        ], $overrides);
    }
}
