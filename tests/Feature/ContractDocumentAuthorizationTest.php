<?php

namespace Tests\Feature;

use App\Models\ContractBatch;
use App\Models\User;
use App\Policies\ContractBatchPolicy;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('legal');
    }

    public function test_admin_and_attached_operator_can_view_contract_batch_documents(): void
    {
        Storage::disk('legal')->put('generated/test/document.pdf', 'pdf');
        Storage::disk('legal')->put('generated/test/document.docx', 'docx');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $attachedOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $nonStaff = User::factory()->create([
            'role' => 'customer',
        ]);

        $documentKey = ContractBatch::buildGeneratedDocumentKey(ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT);

        $batch = ContractBatch::query()->create([
            'company_key' => ContractBatch::COMPANY_REKREDO_KONSULT_DPK,
            'client_full_name' => 'Иван Иванов',
            'co_applicant_full_name' => null,
            'request_date' => '2026-03-20',
            'selected_document_types' => [ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT],
            'input_payload' => [
                'submitted' => [],
                'derived' => [],
            ],
            'generated_documents' => [
                [
                    'document_key' => $documentKey,
                    'document_type' => ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
                    'label' => ContractBatch::getDocumentTypeLabel(ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT),
                    'variants' => [
                        ContractBatch::DOCUMENT_VARIANT_PDF => [
                            'path' => 'generated/test/document.pdf',
                            'download_name' => 'dogovor.pdf',
                            'mime_type' => 'application/pdf',
                        ],
                        ContractBatch::DOCUMENT_VARIANT_DOCX => [
                            'path' => 'generated/test/document.docx',
                            'download_name' => 'dogovor.docx',
                            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                    ],
                ],
            ],
            'archive_path' => null,
            'archive_file_name' => null,
            'generated_at' => now(),
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $attachedOperator->id,
        ]);

        $policy = new ContractBatchPolicy;
        $adminPanel = (new Panel)->id('admin');

        $this->assertTrue($admin->canAccessPanel($adminPanel));
        $this->assertTrue($attachedOperator->canAccessPanel($adminPanel));

        $this->assertTrue($policy->view($admin, $batch));
        $this->assertTrue($policy->view($attachedOperator, $batch));
        $this->assertFalse($policy->view($otherOperator, $batch));
        $this->assertFalse($policy->view($nonStaff, $batch));

        $this->actingAs($admin)
            ->get(route('admin.contract-batches.documents.download', [$batch, $documentKey]))
            ->assertOk();

        $this->actingAs($attachedOperator)
            ->get(route('admin.contract-batches.documents.download', [$batch, $documentKey]))
            ->assertOk();

        $this->actingAs($attachedOperator)
            ->get(route('admin.contract-batches.documents.download', [$batch, $documentKey, 'format' => ContractBatch::DOCUMENT_VARIANT_DOCX]))
            ->assertOk();

        $this->actingAs($otherOperator)
            ->get(route('admin.contract-batches.documents.download', [$batch, $documentKey]))
            ->assertForbidden();

        $this->actingAs($nonStaff)
            ->get(route('admin.contract-batches.documents.download', [$batch, $documentKey]))
            ->assertForbidden();

        $this->actingAs($nonStaff)
            ->get(route('admin.contract-batches.documents.download', [$batch, $documentKey, 'format' => ContractBatch::DOCUMENT_VARIANT_DOCX]))
            ->assertForbidden();
    }

    public function test_contract_12m_batches_share_the_same_permissions_as_existing_layouts(): void
    {
        Storage::disk('legal')->put('generated/test12m/document.docx', 'docx');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $attachedOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $nonStaff = User::factory()->create([
            'role' => 'customer',
        ]);

        $documentKey = ContractBatch::buildGeneratedDocumentKey(
            ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
            1,
        );

        $batch = ContractBatch::query()->create([
            'company_key' => ContractBatch::COMPANY_REKREDO_KONSULT_DPK,
            'document_layout' => ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
            'client_full_name' => 'Иван Иванов',
            'co_applicant_full_name' => 'Мария Иванова',
            'request_date' => '2026-05-18',
            'selected_document_types' => ContractBatch::getDocumentTypesForLayout(
                ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
            ),
            'input_payload' => ['submitted' => [], 'derived' => []],
            'generated_documents' => [
                [
                    'document_key' => $documentKey,
                    'document_type' => ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
                    'copy_number' => 1,
                    'label' => ContractBatch::getGeneratedDocumentLabel(
                        ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
                        1,
                    ),
                    'variants' => [
                        ContractBatch::DOCUMENT_VARIANT_DOCX => [
                            'path' => 'generated/test12m/document.docx',
                            'download_name' => 'deklaraciia.docx',
                            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                    ],
                ],
            ],
            'generated_at' => now(),
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $attachedOperator->id,
        ]);

        $policy = new ContractBatchPolicy;

        $this->assertTrue($policy->view($admin, $batch));
        $this->assertTrue($policy->view($attachedOperator, $batch));
        $this->assertFalse($policy->view($otherOperator, $batch));
        $this->assertFalse($policy->view($nonStaff, $batch));

        $this->assertTrue($policy->update($admin, $batch));
        $this->assertTrue($policy->update($attachedOperator, $batch));
        $this->assertFalse($policy->update($otherOperator, $batch));

        $this->assertTrue($policy->delete($admin, $batch));
        $this->assertTrue($policy->delete($attachedOperator, $batch));
        $this->assertFalse($policy->delete($otherOperator, $batch));

        $this->assertTrue($policy->attach($admin, $batch));
        $this->assertTrue($policy->attach($attachedOperator, $batch));
        $this->assertFalse($policy->attach($otherOperator, $batch));

        $this->actingAs($admin)
            ->get(route('admin.contract-batches.documents.download', [
                $batch,
                $documentKey,
                'format' => ContractBatch::DOCUMENT_VARIANT_DOCX,
            ]))
            ->assertOk();

        $this->actingAs($attachedOperator)
            ->get(route('admin.contract-batches.documents.download', [
                $batch,
                $documentKey,
                'format' => ContractBatch::DOCUMENT_VARIANT_DOCX,
            ]))
            ->assertOk();

        $this->actingAs($otherOperator)
            ->get(route('admin.contract-batches.documents.download', [
                $batch,
                $documentKey,
                'format' => ContractBatch::DOCUMENT_VARIANT_DOCX,
            ]))
            ->assertForbidden();

        $this->actingAs($nonStaff)
            ->get(route('admin.contract-batches.documents.download', [
                $batch,
                $documentKey,
                'format' => ContractBatch::DOCUMENT_VARIANT_DOCX,
            ]))
            ->assertForbidden();
    }

    public function test_every_staff_role_can_create_and_attach_only_own_contracts(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $attachedOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $batch = ContractBatch::query()->create([
            'company_key' => ContractBatch::COMPANY_REKREDO_KONSULT_DPK,
            'client_full_name' => 'Иван Иванов',
            'co_applicant_full_name' => null,
            'request_date' => '2026-03-20',
            'selected_document_types' => [],
            'input_payload' => ['submitted' => [], 'derived' => []],
            'generated_documents' => [],
            'generated_at' => now(),
            'created_by_user_id' => $admin->id,
            'attached_user_id' => $attachedOperator->id,
        ]);

        $policy = new ContractBatchPolicy;

        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->create($attachedOperator));
        $this->assertTrue($policy->create($otherOperator));

        $this->assertTrue($policy->update($admin, $batch));
        $this->assertTrue($policy->update($attachedOperator, $batch));
        $this->assertFalse($policy->update($otherOperator, $batch));

        $this->assertTrue($policy->delete($admin, $batch));
        $this->assertTrue($policy->delete($attachedOperator, $batch));
        $this->assertFalse($policy->delete($otherOperator, $batch));

        $this->assertTrue($policy->attach($admin, $batch));
        $this->assertTrue($policy->attach($attachedOperator, $batch));
        $this->assertFalse($policy->attach($otherOperator, $batch));
    }
}
