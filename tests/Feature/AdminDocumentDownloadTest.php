<?php

namespace Tests\Feature;

use App\Models\AdminDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_stored_file_metadata_updates_size_and_mime_type(): void
    {
        Storage::disk('local')->put('admin-documents/credit-guide.pdf', '%PDF-1.4 test');

        $document = AdminDocument::query()->create([
            'title' => 'Кредитен наръчник',
            'description' => null,
            'file_path' => 'admin-documents/credit-guide.pdf',
            'original_file_name' => 'credit-guide.pdf',
            'uploaded_by_user_id' => null,
        ]);

        $document->syncStoredFileMetadata();
        $document->refresh();

        $this->assertSame(13, $document->file_size);
        $this->assertNotNull($document->mime_type);
    }

    public function test_staff_user_can_download_document_with_original_name(): void
    {
        Storage::disk('local')->put('admin-documents/offer.docx', 'doc-content');

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $document = AdminDocument::query()->create([
            'title' => 'Оферта',
            'description' => null,
            'file_path' => 'admin-documents/offer.docx',
            'original_file_name' => 'offer.docx',
            'uploaded_by_user_id' => $operator->id,
        ]);

        $response = $this
            ->actingAs($operator)
            ->get(route('admin.documents.download', $document));

        $response->assertOk();
        $this->assertStringContainsString(
            'offer.docx',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_staff_user_can_open_pdf_inline(): void
    {
        Storage::disk('local')->put('admin-documents/guide.pdf', '%PDF-1.4 test');

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $document = AdminDocument::query()->create([
            'title' => 'Наръчник',
            'description' => null,
            'file_path' => 'admin-documents/guide.pdf',
            'original_file_name' => 'guide.pdf',
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $operator->id,
        ]);

        $response = $this
            ->actingAs($operator)
            ->get(route('admin.documents.open', $document));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type'),
        );
    }

    public function test_staff_user_cannot_open_unsafe_file_inline(): void
    {
        Storage::disk('local')->put('admin-documents/offer.docx', 'doc-content');

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $document = AdminDocument::query()->create([
            'title' => 'Оферта',
            'description' => null,
            'file_path' => 'admin-documents/offer.docx',
            'original_file_name' => 'offer.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'uploaded_by_user_id' => $operator->id,
        ]);

        $this
            ->actingAs($operator)
            ->get(route('admin.documents.open', $document))
            ->assertForbidden();
    }

    public function test_non_staff_user_cannot_download_shared_document(): void
    {
        Storage::disk('local')->put('admin-documents/internal.xlsx', 'sheet');

        $nonStaff = User::factory()->create([
            'role' => 'customer',
        ]);

        $document = AdminDocument::query()->create([
            'title' => 'Вътрешен отчет',
            'description' => null,
            'file_path' => 'admin-documents/internal.xlsx',
            'original_file_name' => 'internal.xlsx',
            'uploaded_by_user_id' => null,
        ]);

        $this
            ->actingAs($nonStaff)
            ->get(route('admin.documents.download', $document))
            ->assertForbidden();
    }
}
