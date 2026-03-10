<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'subject' => $this->normalizeString($this->input('subject')),
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
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $phone = $this->input('phone');
            $email = $this->input('email');

            if ($this->isBlank($phone) && $this->isBlank($email)) {
                $message = 'Моля, посочете телефон или имейл за обратна връзка.';

                $validator->errors()->add('phone', $message);
                $validator->errors()->add('email', $message);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Полето за име и фамилия е задължително.',
            'full_name.max' => 'Името е твърде дълго.',
            'phone.max' => 'Телефонът е твърде дълъг.',
            'email.email' => 'Моля, въведете валиден имейл адрес.',
            'email.max' => 'Имейл адресът е твърде дълъг.',
            'subject.max' => 'Темата е твърде дълга.',
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

    private function isBlank(mixed $value): bool
    {
        return !is_string($value) || trim($value) === '';
    }
}
