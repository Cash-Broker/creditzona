<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'credit_type' => $this->normalizeString($this->input('credit_type')),
            'first_name' => $this->normalizeString($this->input('first_name')),
            'last_name' => $this->normalizeString($this->input('last_name')),
            'phone' => $this->normalizeString($this->input('phone')),
            'email' => $this->normalizeString($this->input('email')),
            'city' => $this->normalizeString($this->input('city')),
            'property_type' => $this->normalizeString($this->input('property_type')),
            'property_location' => $this->normalizeString($this->input('property_location')),
        ]);
    }

    /**
     * @return array<string, array<int, Closure|string>>
     */
    public function rules(): array
    {
        return [
            'credit_type' => ['required', 'in:consumer,mortgage'],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => [
                'bail',
                'required',
                'string',
                'max:30',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $hasRecentLead = Lead::query()
                        ->where('phone', $value)
                        ->where('created_at', '>=', now()->subDays(14))
                        ->exists();

                    if ($hasRecentLead) {
                        $fail('Вече има подадена заявка с този телефонен номер през последните 14 дни.');
                    }
                },
            ],
            'email' => ['required', 'email', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'min:5000', 'max:50000'],
            'property_type' => ['nullable', 'required_if:credit_type,mortgage', 'in:house,apartment'],
            'property_location' => ['nullable', 'required_if:credit_type,mortgage', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'credit_type.required' => 'Моля, изберете тип кредит.',
            'credit_type.in' => 'Моля, изберете валиден тип кредит.',

            'first_name.required' => 'Моля, въведете вашето име.',
            'first_name.string' => 'Името трябва да бъде текст.',
            'first_name.max' => 'Името не може да бъде по-дълго от 60 символа.',

            'last_name.required' => 'Моля, въведете вашата фамилия.',
            'last_name.string' => 'Фамилията трябва да бъде текст.',
            'last_name.max' => 'Фамилията не може да бъде по-дълга от 60 символа.',

            'phone.required' => 'Моля, въведете телефон за връзка.',
            'phone.string' => 'Телефонът трябва да бъде текст.',
            'phone.max' => 'Телефонът не може да бъде по-дълъг от 30 символа.',

            'email.required' => 'Моля, въведете имейл адрес.',
            'email.email' => 'Моля, въведете валиден имейл адрес.',
            'email.max' => 'Имейл адресът не може да бъде по-дълъг от 120 символа.',

            'city.required' => 'Моля, въведете вашия град.',
            'city.string' => 'Градът трябва да бъде текст.',
            'city.max' => 'Градът не може да бъде по-дълъг от 120 символа.',

            'amount.required' => 'Моля, изберете желаната сума.',
            'amount.integer' => 'Сумата трябва да бъде цяло число.',
            'amount.min' => 'Сумата трябва да бъде поне 5000.',
            'amount.max' => 'Сумата не може да бъде повече от 50000.',

            'property_type.required_if' => 'Моля, изберете вид на имота.',
            'property_type.in' => 'Моля, изберете валиден вид на имота.',

            'property_location.required_if' => 'Моля, въведете местонахождение на имота.',
            'property_location.string' => 'Местонахождението на имота трябва да бъде текст.',
            'property_location.max' => 'Местонахождението на имота не може да бъде по-дълго от 120 символа.',
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
