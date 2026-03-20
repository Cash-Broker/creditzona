<?php

namespace Tests\Unit;

use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Models\Lead;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Tests\TestCase;

class LeadAdminFormRequiredFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_admin_fields_remain_required_while_optional_sections_stay_optional(): void
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

        $form = $component->form(Schema::make($component));
        $form->fill([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
        ]);

        $fields = $form->getFlatFields(withHidden: true);

        foreach ([
            'middle_name',
            'egn',
            'workplace',
            'job_title',
            'salary',
            'marital_status',
            'children_under_18',
            'salary_bank',
            'credit_bank',
        ] as $field) {
            $this->assertArrayHasKey($field, $fields);
            $this->assertTrue($fields[$field]->isRequired(), "Expected [{$field}] to be required.");
        }

        $this->assertFalse($fields['guarantors']->isRequired());
        $this->assertFalse($fields['documents']->isRequired());
        $this->assertFalse($fields['internal_notes']->isRequired());
    }

    public function test_consumer_with_guarantor_admin_fields_keep_consumer_fields_required_while_guarantor_section_stays_optional(): void
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

        $form = $component->form(Schema::make($component));
        $form->fill([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
        ]);

        $fields = $form->getFlatFields(withHidden: true);

        foreach ([
            'middle_name',
            'egn',
            'email',
            'city',
            'workplace',
            'job_title',
            'salary',
            'marital_status',
            'children_under_18',
            'salary_bank',
            'credit_bank',
        ] as $field) {
            $this->assertArrayHasKey($field, $fields);
            $this->assertTrue($fields[$field]->isRequired(), "Expected [{$field}] to be required for consumer leads with guarantor.");
        }

        $this->assertFalse($fields['guarantors']->isRequired());

        $guarantorFields = $this->getGuarantorSchemaFields();

        foreach ([
            'status',
            'amount',
            'first_name',
            'middle_name',
            'last_name',
            'egn',
            'phone',
            'email',
            'city',
            'workplace',
            'job_title',
            'salary',
            'marital_status',
            'children_under_18',
            'salary_bank',
            'credit_bank',
            'property_type',
            'property_location',
        ] as $field) {
            $this->assertArrayHasKey($field, $guarantorFields);
            $this->assertFalse($guarantorFields[$field]->isRequired(), "Expected guarantor field [{$field}] to stay optional in admin.");
        }
    }

    public function test_sms_email_and_rejected_statuses_do_not_require_full_application_fields_in_admin(): void
    {
        foreach (['sms', 'email', 'rejected'] as $status) {
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

            $form = $component->form(Schema::make($component));
            $form->fill([
                'status' => $status,
                'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            ]);

            $fields = $form->getFlatFields(withHidden: true);

            foreach ([
                'middle_name',
                'egn',
                'workplace',
                'job_title',
                'salary',
                'marital_status',
                'children_under_18',
                'salary_bank',
                'credit_bank',
                'guarantors',
            ] as $field) {
                $this->assertArrayHasKey($field, $fields);
                $this->assertFalse($fields[$field]->isRequired(), "Expected [{$field}] to be optional for status [{$status}].");
            }
        }
    }

    public function test_status_field_is_live_in_admin_form(): void
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

        $fields = $component
            ->form(Schema::make($component))
            ->getFlatFields(withHidden: true);

        $this->assertTrue($fields['status']->isLive());
    }

    /**
     * @return array<string, mixed>
     */
    private function getGuarantorSchemaFields(): array
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
}
