<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadSensitiveDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_lead_fields_are_encrypted_and_admin_document_fields_are_cast_correctly(): void
    {
        $lead = Lead::query()->create($this->leadData([
            'egn' => '1234567890',
            'credit_bank' => 'Bank DSK',
            'documents' => [
                'lead-documents/1/application.pdf',
            ],
            'document_file_names' => [
                'lead-documents/1/application.pdf' => 'application.pdf',
            ],
        ]));

        $guarantor = $lead->guarantors()->create([
            'first_name' => 'Maria',
            'last_name' => 'Petrova',
            'egn' => '0987654321',
            'phone' => '0888000111',
            'status' => LeadGuarantor::STATUS_SUITABLE,
        ]);

        $this->assertNotSame('1234567890', DB::table('leads')->where('id', $lead->id)->value('egn'));
        $this->assertNotSame('0987654321', DB::table('lead_guarantors')->where('id', $guarantor->id)->value('egn'));

        $lead->refresh();
        $guarantor->refresh();

        $this->assertSame('1234567890', $lead->egn);
        $this->assertSame('0987654321', $guarantor->egn);
        $this->assertSame('Bank DSK', $lead->credit_bank);
        $this->assertSame([
            'lead-documents/1/application.pdf',
        ], $lead->documents);
        $this->assertSame([
            'lead-documents/1/application.pdf' => 'application.pdf',
        ], $lead->document_file_names);
        $this->assertSame([
            'application.pdf',
        ], $lead->getDocumentDisplayNames());
    }

    public function test_egn_mask_helpers_keep_only_last_four_digits_visible(): void
    {
        $this->assertSame('******7890', Lead::maskEgn('1234567890'));
        $this->assertSame('******4321', LeadGuarantor::maskEgn('0987654321'));
        $this->assertSame('Няма', Lead::maskEgn(null));
    }

    public function test_existing_private_documents_prepare_download_links(): void
    {
        Storage::disk('local')->put('lead-documents/1/application.pdf', 'test-content');

        $lead = Lead::query()->create($this->leadData([
            'documents' => [
                'lead-documents/1/application.pdf',
            ],
            'document_file_names' => [
                'lead-documents/1/application.pdf' => 'application.pdf',
            ],
        ]));

        $documents = $lead->getDocumentDownloads();

        $this->assertCount(1, $documents);
        $this->assertSame('application.pdf', $documents[0]['name']);
        $this->assertSame('lead-documents/1/application.pdf', $documents[0]['path']);
        $this->assertTrue($documents[0]['is_available']);
    }

    public function test_authorized_staff_downloads_document_with_original_file_name(): void
    {
        Storage::disk('local')->put('lead-documents/1/application.pdf', 'test-content');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $lead = Lead::query()->create($this->leadData([
            'documents' => [
                'lead-documents/1/application.pdf',
            ],
            'document_file_names' => [
                'lead-documents/1/application.pdf' => 'application.pdf',
            ],
        ]));

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.leads.documents.download', [
                'lead' => $lead,
                'path' => 'lead-documents/1/application.pdf',
            ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'application.pdf',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_unrelated_operator_cannot_download_attached_document(): void
    {
        Storage::disk('local')->put('lead-documents/1/application.pdf', 'test-content');

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $lead = Lead::query()->create($this->leadData([
            'documents' => [
                'lead-documents/1/application.pdf',
            ],
            'document_file_names' => [
                'lead-documents/1/application.pdf' => 'application.pdf',
            ],
        ]));

        $this
            ->actingAs($operator)
            ->get(route('admin.leads.documents.download', [
                'lead' => $lead,
                'path' => 'lead-documents/1/application.pdf',
            ]))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'credit_type' => 'consumer',
            'first_name' => 'Ivan',
            'middle_name' => null,
            'last_name' => 'Ivanov',
            'egn' => null,
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Plovdiv',
            'workplace' => null,
            'job_title' => null,
            'salary' => null,
            'marital_status' => null,
            'children_under_18' => null,
            'salary_bank' => null,
            'credit_bank' => null,
            'documents' => null,
            'document_file_names' => null,
            'internal_notes' => null,
            'amount' => 10000,
            'property_type' => null,
            'property_location' => null,
            'status' => 'new',
            'assigned_user_id' => null,
            'source' => null,
            'utm_source' => null,
            'utm_campaign' => null,
            'utm_medium' => null,
            'gclid' => null,
        ], $overrides);
    }
}
