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

    public function test_consumer_with_guarantor_admin_fields_keep_consumer_fields_required_and_require_guarantor_section(): void
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

        $this->assertTrue($fields['guarantors']->isRequired());
        $this->assertTrue($fields['amount']->isRequired());
        $this->assertTrue($fields['first_name']->isRequired());
        $this->assertTrue($fields['last_name']->isRequired());
        $this->assertTrue($fields['phone']->isRequired());
    }
}
