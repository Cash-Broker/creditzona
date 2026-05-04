<?php

namespace Tests\Feature;

use App\Filament\Resources\AttachedContractBatches\AttachedContractBatchResource;
use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Models\ContractBatch;
use App\Models\User;
use App\Services\ContractBatchService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
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

    public function test_non_admin_cannot_attach_contract_batches(): void
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

        $this->expectException(AuthorizationException::class);

        app(ContractBatchService::class)->attachToOperator($batch, $otherOperator, $operator);
    }

    public function test_cannot_attach_contract_batch_to_admin(): void
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

        $this->expectException(DomainException::class);

        app(ContractBatchService::class)->attachToOperator($batch, $otherAdmin, $admin);
    }

    public function test_contract_batch_resource_query_is_admin_only(): void
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

        $this->actingAs($admin);
        $this->assertSame([$batch->id], ContractBatchResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue(ContractBatchResource::canViewAny());

        $this->actingAs($operator);
        $this->assertSame([], ContractBatchResource::getEloquentQuery()->pluck('id')->all());
        $this->assertFalse(ContractBatchResource::canViewAny());
    }

    public function test_attached_resource_query_returns_only_contracts_attached_to_current_operator(): void
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
        $this->assertSame([$attachedToAnna->id], AttachedContractBatchResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue(AttachedContractBatchResource::canViewAny());

        $this->actingAs($admin);
        $this->assertFalse(AttachedContractBatchResource::canViewAny());
    }

    public function test_attached_resource_disallows_create_edit_delete(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $batch = $this->createBatch([
            'attached_user_id' => $anna->id,
            'created_by_user_id' => $anna->id,
        ]);

        $this->actingAs($anna);

        $this->assertFalse(AttachedContractBatchResource::canCreate());
        $this->assertFalse(AttachedContractBatchResource::canEdit($batch));
        $this->assertFalse(AttachedContractBatchResource::canDelete($batch));
        $this->assertFalse(AttachedContractBatchResource::canDeleteAny());
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
