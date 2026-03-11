<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->normalizeString($this->input('full_name')),
            'phone' => $this->normalizeString($this->input('phone')),
            'email' => $this->normalizeString($this->input('email')),
            'message' => $this->normalizeString($this->input('message')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Полето за име и фамилия е задължително.',
            'full_name.max' => 'Името е твърде дълго.',
            'phone.required' => 'Полето за телефон е задължително.',
            'phone.max' => 'Телефонът е твърде дълъг.',
            'email.required' => 'Полето за имейл е задължително.',
            'email.email' => 'Моля, въведете валиден имейл адрес.',
            'email.max' => 'Имейл адресът е твърде дълъг.',
            'message.required' => 'Полето за съобщение е задължително.',
            'message.min' => 'Съобщението трябва да е поне 10 символа.',
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
