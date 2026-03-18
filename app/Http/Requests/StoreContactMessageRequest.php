<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ProtectsPublicForms;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    use ProtectsPublicForms;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->normalizeString($this->input('full_name')),
            'phone' => $this->normalizePhone($this->input('phone')),
            'email' => $this->normalizeString($this->input('email')),
            'message' => $this->normalizeString($this->input('message')),
        ]);

        $this->preparePublicFormProtection();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ], $this->publicFormProtectionRules());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge([
            'full_name.required' => 'Полето за име и фамилия е задължително.',
            'full_name.max' => 'Името е твърде дълго.',
            'phone.required' => 'Полето за телефон е задължително.',
            'phone.max' => 'Телефонът е твърде дълъг.',
            'email.required' => 'Полето за имейл е задължително.',
            'email.email' => 'Моля, въведете валиден имейл адрес.',
            'email.max' => 'Имейлът е твърде дълъг.',
            'message.required' => 'Полето за съобщение е задължително.',
            'message.min' => 'Съобщението трябва да съдържа поне 10 символа.',
            'message.max' => 'Съобщението е твърде дълго.',
        ], $this->publicFormProtectionMessages());
    }
}
