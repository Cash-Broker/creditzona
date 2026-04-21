<?php

namespace App\Http\Requests\Api;

use App\Filament\Resources\Leads\LeadResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(LeadResource::getStatusOptions()))],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Статусът е задължителен.',
            'status.in' => 'Избраният статус е невалиден.',
        ];
    }
}
