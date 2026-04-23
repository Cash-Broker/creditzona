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

        $this->assertArrayHasKey('full_name', $fields);
        $this->assertTrue($fields['full_name']->isRequired());

        $this->assertArrayHasKey('egn', $fields);
        $this->assertTrue($fields['egn']->isRequired());

        foreach ([
            'workplace',
            'job_title',
            'salary',
            'marital_status',
            'children_under_18',
            'salary_bank',
            'credit_bank',
            'phone',
            'email',
            'city',
        ] as $field) {
            $this->assertArrayHasKey($field, $fields);
            $this->assertFalse($fields[$field]->isRequired(), "Expected [{$field}] to stay optional.");
        }

        $this->assertFalse($fields['guarantors']->isRequired());
        $this->assertFalse($fields['documents']->isRequired());
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

        $this->assertArrayHasKey('full_name', $fields);
        $this->assertTrue($fields['full_name']->isRequired());

        $this->assertArrayHasKey('egn', $fields);
        $this->assertTrue($fields['egn']->isRequired());

        foreach ([
            'email',
            'city',
            'workplace',
            'job_title',
            'salary',
            'marital_status',
            'children_under_18',
            'salary_bank',
            'credit_bank',
            'phone',
        ] as $field) {
            $this->assertArrayHasKey($field, $fields);
            $this->assertFalse($fields[$field]->isRequired(), "Expected [{$field}] to stay optional for consumer leads with guarantor.");
        }

        $this->assertFalse($fields['guarantors']->isRequired());

        $guarantorFields = $this->getGuarantorSchemaFields();

        $this->assertArrayHasKey('full_name', $guarantorFields);
        $this->assertFalse($guarantorFields['full_name']->isRequired());

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

            $this->assertTrue($fields['full_name']->isRequired(), "Expected [full_name] to stay required for status [{$status}].");
            $this->assertFalse($fields['egn']->isRequired(), "Expected [egn] to stay optional for status [{$status}].");

            foreach ([
                'workplace',
                'job_title',
                'salary',
                'marital_status',
                'children_under_18',
                'salary_bank',
                'credit_bank',
                'guarantors',
                'phone',
                'email',
                'city',
            ] as $field) {
                $this->assertArrayHasKey($field, $fields);
                $this->assertFalse($fields[$field]->isRequired(), "Expected [{$field}] to be optional for status [{$status}].");
            }
        }
    }

    public function test_recurring_status_makes_all_fields_optional_in_admin(): void
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
            'status' => 'recurring',
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
        ]);

        $fields = $form->getFlatFields(withHidden: true);

        foreach ([
            'assigned_user_id',
            'full_name',
            'egn',
            'credit_type',
            'amount',
            'workplace',
            'job_title',
            'salary',
            'marital_status',
            'children_under_18',
            'salary_bank',
            'credit_bank',
            'phone',
            'email',
            'city',
            'guarantors',
            'documents',
        ] as $field) {
            $this->assertArrayHasKey($field, $fields);
            $this->assertFalse(
                $fields[$field]->isRequired(),
                "Expected [{$field}] to be optional for status [recurring].",
            );
        }

        // Status itself stays required — the operator must explicitly pick it.
        $this->assertTrue($fields['status']->isRequired());
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
