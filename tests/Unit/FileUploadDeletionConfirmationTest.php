<?php

namespace Tests\Unit;

use App\Filament\Resources\AdminDocuments\Schemas\AdminDocumentForm;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Models\Lead;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Tests\TestCase;

class FileUploadDeletionConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_document_file_upload_confirms_before_deletion(): void
    {
        $fields = $this->adminDocumentFormFields();

        $this->assertUploadedFileDeletionConfirmation($fields['file_path']);
    }

    public function test_lead_document_uploads_confirm_before_deletion(): void
    {
        $fields = $this->leadFormFields();
        $guarantorFields = $this->guarantorFormFields();

        $this->assertUploadedFileDeletionConfirmation($fields['documents']);
        $this->assertUploadedFileDeletionConfirmation($guarantorFields['documents']);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminDocumentFormFields(): array
    {
        $component = new class extends Component implements HasForms
        {
            use InteractsWithForms;

            public ?array $data = [];

            public function form(Schema $schema): Schema
            {
                return AdminDocumentForm::configure($schema)
                    ->statePath('data');
            }

            public function render(): string
            {
                return '';
            }
        };

        return $component
            ->form(Schema::make($component))
            ->getFlatFields(withHidden: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function leadFormFields(): array
    {
        $component = new class extends Component implements HasForms
        {
            use InteractsWithForms;

            public ?array $data = [];

            public function form(Schema $schema): Schema
            {
                return LeadForm::configure($schema)
                    ->model(Lead::class)
                    ->statePath('data');
            }

            public function render(): string
            {
                return '';
            }
        };

        return $component
            ->form(Schema::make($component))
            ->getFlatFields(withHidden: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function guarantorFormFields(): array
    {
        $method = new \ReflectionMethod(LeadForm::class, 'guarantorSchema');
        $method->setAccessible(true);

        $component = new class extends Component implements HasForms
        {
            use InteractsWithForms;

            public array $components = [];

            public ?array $data = [];

            public function form(Schema $schema): Schema
            {
                return $schema
                    ->components($this->components)
                    ->statePath('data');
            }

            public function render(): string
            {
                return '';
            }
        };

        $component->components = $method->invoke(null);

        return $component
            ->form(Schema::make($component))
            ->getFlatFields(withHidden: true);
    }

    private function assertUploadedFileDeletionConfirmation(mixed $field): void
    {
        $this->assertInstanceOf(FileUpload::class, $field);

        $attributes = $field->getExtraAlpineAttributes();

        $this->assertArrayHasKey('x-init', $attributes);
        $this->assertStringContainsString('beforeRemoveFile', $attributes['x-init']);
        $this->assertStringContainsString('window.confirm', $attributes['x-init']);
    }
}
