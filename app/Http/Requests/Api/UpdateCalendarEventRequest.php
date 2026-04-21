<?php

namespace App\Http\Requests\Api;

use App\Models\CalendarEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['nullable', 'date'],
            'all_day' => ['nullable', 'boolean'],
            'event_type' => ['sometimes', 'required', 'string', Rule::in(array_keys(CalendarEvent::getEventTypeOptions()))],
            'status' => ['nullable', 'string', Rule::in(array_keys(CalendarEvent::getStatusOptions()))],
            'color' => ['nullable', 'string', 'max:7'],
            'reminder_minutes_before' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Заглавието е задължително.',
            'starts_at.required' => 'Началната дата е задължителна.',
            'event_type.in' => 'Невалиден тип събитие.',
            'status.in' => 'Невалиден статус.',
        ];
    }
}
