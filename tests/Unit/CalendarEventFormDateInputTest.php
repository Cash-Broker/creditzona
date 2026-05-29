<?php

namespace Tests\Unit;

use App\Filament\Forms\ManualCalendarDateTimeInput;
use App\Filament\Resources\CalendarEvents\Schemas\CalendarEventForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Tests\TestCase;

class CalendarEventFormDateInputTest extends TestCase
{
    public function test_calendar_form_uses_start_date_input_without_end_field(): void
    {
        $fields = $this->calendarEventFormFields();

        $this->assertManualCalendarDateTimeInput($fields['starts_at']);
        $this->assertArrayNotHasKey('ends_at', $fields);
    }

    /**
     * @return array<string, mixed>
     */
    private function calendarEventFormFields(): array
    {
        $component = new class extends Component implements HasForms
        {
            use InteractsWithForms;

            public ?array $data = [];

            public function form(Schema $schema): Schema
            {
                return $schema
                    ->components(CalendarEventForm::schema(canManageUsers: true, userOptions: []))
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

    private function assertManualCalendarDateTimeInput(mixed $field): void
    {
        $this->assertInstanceOf(DateTimePicker::class, $field);

        $this->assertFalse($field->isNative());
        $this->assertFalse($field->hasSeconds());
        $this->assertSame(ManualCalendarDateTimeInput::DISPLAY_FORMAT, $field->getDisplayFormat());
        $this->assertSame(ManualCalendarDateTimeInput::PLACEHOLDER, $field->getPlaceholder());

        $attributes = $field->getExtraAlpineAttributes();

        $this->assertArrayHasKey('x-init', $attributes);
        $this->assertStringContainsString("removeAttribute('readonly')", $attributes['x-init']);
        $this->assertStringContainsString('DD/MM/YYYY HH:mm', $attributes['x-init']);
        $this->assertStringContainsString('setState(parsedDate)', $attributes['x-init']);
    }
}
