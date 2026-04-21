<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'guarantor_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Съобщението е задължително.',
            'body.max' => 'Съобщението не може да надвишава 5000 символа.',
        ];
    }
}
