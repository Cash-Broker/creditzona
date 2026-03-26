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

    public function test_only_staff_users_can_access_contract_batch_downloads(): void
    {
        Storage::disk('legal')->put('generated/test/document.pdf', 'pdf');

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
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
                    'path' => 'generated/test/document.pdf',
                    'download_name' => 'dogovor.pdf',
                    'mime_type' => 'application/pdf',
                ],
            ],
            'archive_path' => null,
            'archive_file_name' => null,
            'generated_at' => now(),
            'created_by_user_id' => $operator->id,
        ]);

        $policy = new ContractBatchPolicy;
        $adminPanel = (new Panel)->id('admin');

        $this->assertTrue($operator->canAccessPanel($adminPanel));
        $this->assertTrue($policy->view($operator, $batch));
        $this->assertFalse($policy->view($nonStaff, $batch));

        $this->actingAs($operator)
            ->get(route('admin.contract-batches.documents.download', [$batch, $documentKey]))
            ->assertOk();

        $this->actingAs($nonStaff)
            ->get(route('admin.contract-batches.documents.download', [$batch, $documentKey]))
            ->assertForbidden();
    }
}
