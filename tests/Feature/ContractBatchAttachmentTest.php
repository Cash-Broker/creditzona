<?php

namespace Tests\Feature;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Models\ContractBatch;
use App\Models\User;
use App\Services\ContractBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractBatchAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('legal');
    }

    public function test_admin_can_attach_contract_batch_to_operator(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = $this->createBatch(['created_by_user_id' => $admin->id]);

        $updated = app(ContractBatchService::class)->attachToOperator($batch, $operator, $admin);

        $this->assertSame($operator->id, $updated->attached_user_id);
        $this->assertTrue($updated->relationLoaded('attachedUser'));
        $this->assertSame($operator->id, $updated->attachedUser?->id);
    }

    public function test_admin_can_remove_attachment_by_passing_null_operator(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $operator->id,
        ]);

        $updated = app(ContractBatchService::class)->attachToOperator($batch, null, $admin);

        $this->assertNull($updated->attached_user_id);
    }

    public function test_any_user_can_attach_contract_batches(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $batch = $this->createBatch(['created_by_user_id' => $operator->id]);

        $updated = app(ContractBatchService::class)->attachToOperator($batch, $otherOperator, $operator);

        $this->assertSame($otherOperator->id, $updated->attached_user_id);
    }

    public function test_can_attach_contract_batch_to_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $otherAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin2@creditzona.test',
        ]);

        $batch = $this->createBatch(['created_by_user_id' => $admin->id]);

        $updated = app(ContractBatchService::class)->attachToOperator($batch, $otherAdmin, $admin);

        $this->assertSame($otherAdmin->id, $updated->attached_user_id);
    }

    public function test_admin_sees_all_contracts_in_main_resource(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $ownBatch = $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $operator->id,
            'client_full_name' => 'Свой клиент',
        ]);

        $foreignAttached = $this->createBatch([
            'created_by_user_id' => $otherOperator->id,
            'attached_user_id' => $operator->id,
            'client_full_name' => 'Чужд клиент - прикачен',
        ]);

        $foreignUnattached = $this->createBatch([
            'created_by_user_id' => $otherOperator->id,
            'attached_user_id' => null,
            'client_full_name' => 'Чужд клиент - неприкачен',
        ]);

        $this->actingAs($admin);
        $this->assertTrue(ContractBatchResource::canViewAny());
        $this->assertEqualsCanonicalizing(
            [$ownBatch->id, $foreignAttached->id, $foreignUnattached->id],
            ContractBatchResource::getEloquentQuery()->pluck('id')->all(),
        );
        $this->assertTrue(ContractBatchResource::canView($ownBatch));
        $this->assertTrue(ContractBatchResource::canView($foreignAttached));
        $this->assertTrue(ContractBatchResource::canView($foreignUnattached));
    }

    public function test_operator_main_resource_query_only_returns_contracts_attached_to_them(): void
    {
        $admin = User::factory()->create([
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

        $attachedToAnna = $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $anna->id,
            'client_full_name' => 'Първи клиент',
        ]);

        $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $elena->id,
            'client_full_name' => 'Втори клиент',
        ]);

        $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => null,
            'client_full_name' => 'Трети клиент',
        ]);

        $this->actingAs($anna);
        $this->assertTrue(ContractBatchResource::canViewAny());
        $this->assertSame([$attachedToAnna->id], ContractBatchResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_user_with_view_all_flag_sees_every_contract(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
            'can_view_all_contracts' => true,
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $attachedToAnna = $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $anna->id,
        ]);

        $unattached = $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => null,
            'client_full_name' => 'Друг клиент',
        ]);

        $this->actingAs($elena);

        $this->assertTrue(ContractBatchResource::canViewAny());
        $this->assertEqualsCanonicalizing(
            [$attachedToAnna->id, $unattached->id],
            ContractBatchResource::getEloquentQuery()->pluck('id')->all(),
        );
        $this->assertTrue(ContractBatchResource::canView($attachedToAnna));
        $this->assertTrue(ContractBatchResource::canView($unattached));
    }

    public function test_user_with_view_all_flag_has_full_create_edit_delete_attach_rights(): void
    {
        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
            'can_view_all_contracts' => true,
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = $this->createBatch([
            'created_by_user_id' => $anna->id,
            'attached_user_id' => $anna->id,
        ]);

        $this->actingAs($elena);

        $this->assertTrue(ContractBatchResource::canCreate());
        $this->assertTrue(ContractBatchResource::canEdit($batch));
        $this->assertTrue(ContractBatchResource::canDelete($batch));
        $this->assertTrue(ContractBatchResource::canDeleteAny());

        $policy = new \App\Policies\ContractBatchPolicy;

        $this->assertTrue($policy->create($elena));
        $this->assertTrue($policy->update($elena, $batch));
        $this->assertTrue($policy->delete($elena, $batch));
        $this->assertTrue($policy->deleteAny($elena));
        $this->assertTrue($policy->attach($elena, $batch));
    }

    public function test_operator_without_view_all_flag_can_create_and_manage_only_their_own(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $ownBatch = $this->createBatch([
            'created_by_user_id' => $anna->id,
            'attached_user_id' => $anna->id,
        ]);

        $foreignBatch = $this->createBatch([
            'created_by_user_id' => $elena->id,
            'attached_user_id' => $elena->id,
        ]);

        $this->actingAs($anna);

        $this->assertTrue(ContractBatchResource::canCreate());
        $this->assertTrue(ContractBatchResource::canDeleteAny());

        $this->assertTrue(ContractBatchResource::canEdit($ownBatch));
        $this->assertTrue(ContractBatchResource::canDelete($ownBatch));

        $this->assertFalse(ContractBatchResource::canEdit($foreignBatch));
        $this->assertFalse(ContractBatchResource::canDelete($foreignBatch));
    }

    public function test_operator_without_flag_only_sees_attached_contracts_in_main_resource(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $attachedToAnna = $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $anna->id,
        ]);

        $createdByAdmin = $this->createBatch([
            'created_by_user_id' => $admin->id,
            'attached_user_id' => null,
        ]);

        $this->actingAs($anna);

        $this->assertTrue(ContractBatchResource::canViewAny());
        $this->assertSame([$attachedToAnna->id], ContractBatchResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue(ContractBatchResource::canView($attachedToAnna));
        $this->assertFalse(ContractBatchResource::canView($createdByAdmin));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBatch(array $overrides = []): ContractBatch
    {
        return ContractBatch::query()->create(array_merge([
            'company_key' => ContractBatch::COMPANY_REKREDO_KONSULT_DPK,
            'client_full_name' => 'Иван Иванов',
            'co_applicant_full_name' => null,
            'request_date' => '2026-04-01',
            'selected_document_types' => [ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT],
            'input_payload' => ['submitted' => [], 'derived' => []],
            'generated_documents' => [],
            'generated_at' => now(),
        ], $overrides));
    }
}
