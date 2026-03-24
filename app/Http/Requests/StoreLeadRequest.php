<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ProtectsPublicForms;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Rules\CyrillicText;
use App\Rules\ExclusiveLeadParticipantPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    use ProtectsPublicForms;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'credit_type' => $this->normalizeString($this->input('credit_type')),
            'first_name' => $this->normalizeString($this->input('first_name')),
            'middle_name' => $this->normalizeString($this->input('middle_name')),
            'last_name' => $this->normalizeString($this->input('last_name')),
            'phone' => $this->normalizePhone($this->input('phone')),
            'email' => $this->normalizeString($this->input('email')),
            'city' => $this->normalizeString($this->input('city')),
            'workplace' => $this->normalizeString($this->input('workplace')),
            'job_title' => $this->normalizeString($this->input('job_title')),
            'marital_status' => $this->normalizeString($this->input('marital_status')),
            'salary_bank' => $this->normalizeString($this->input('salary_bank')),
            'property_type' => $this->normalizeString($this->input('property_type')),
            'property_location' => $this->normalizeString($this->input('property_location')),
            'source' => $this->normalizeString($this->input('source')),
            'utm_source' => $this->normalizeString($this->input('utm_source')),
            'utm_campaign' => $this->normalizeString($this->input('utm_campaign')),
            'utm_medium' => $this->normalizeString($this->input('utm_medium')),
            'gclid' => $this->normalizeString($this->input('gclid')),
            'privacy_consent' => $this->boolean('privacy_consent'),
            'guarantors' => $this->normalizeGuarantors($this->input('guarantors')),
        ]);

        $this->preparePublicFormProtection();
    }

    /**
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    public function rules(): array
    {
        return array_merge([
            'credit_type' => ['required', Rule::in(array_keys(Lead::getPublicCreditTypeOptions()))],
            'first_name' => ['required', 'string', 'max:60', CyrillicText::lettersOnly('Името')],
            'middle_name' => ['nullable', 'string', 'max:60', CyrillicText::lettersOnly('Презимето')],
            'last_name' => ['required', 'string', 'max:60', CyrillicText::lettersOnly('Фамилията')],
            'phone' => [
                'bail',
                'required',
                'string',
                'max:30',
                ExclusiveLeadParticipantPhone::forApplicant(),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $recentLead = $this->findRecentLeadByPhone($value);

                    if ($recentLead !== null) {
                        $fail($this->duplicateLeadMessage($recentLead));
                    }
                },
            ],
            'email' => ['required', 'email', 'max:120'],
            'city' => ['required', 'string', 'max:120', CyrillicText::withoutLatin('Градът')],
            'workplace' => ['nullable', 'string', 'max:120', CyrillicText::withoutLatin('Местоработата')],
            'job_title' => ['nullable', 'string', 'max:120', CyrillicText::withoutLatin('Длъжността')],
            'salary' => ['nullable', 'integer', 'min:0'],
            'marital_status' => ['nullable', Rule::in(array_keys(Lead::getMaritalStatusOptions()))],
            'children_under_18' => ['nullable', 'integer', 'min:0'],
            'salary_bank' => ['nullable', 'string', 'max:120', CyrillicText::withoutLatin('Банката за заплатата')],
            'amount' => ['required', 'integer', 'min:5000', 'max:50000'],
            'property_type' => ['nullable', 'required_if:credit_type,'.Lead::CREDIT_TYPE_MORTGAGE, 'in:house,apartment'],
            'property_location' => ['nullable', 'required_if:credit_type,'.Lead::CREDIT_TYPE_MORTGAGE, 'string', 'max:120', CyrillicText::withoutLatin('Местонахождението на имота')],
            'source' => ['nullable', 'string', 'max:120'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:150'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'gclid' => ['nullable', 'string', 'max:255'],
            'privacy_consent' => ['accepted'],
            'guarantors' => [
                Rule::requiredIf(fn (): bool => $this->isConsumerWithGuarantorLead()),
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->isConsumerWithGuarantorLead() || ! is_array($value) || $value === []) {
                        return;
                    }

                    if (count($value) !== 1) {
                        $fail('При потребителски кредит с поръчител трябва да въведете точно един поръчител.');
                    }
                },
            ],
            'guarantors.*.first_name' => ['required', 'string', 'max:60', CyrillicText::lettersOnly('Името на поръчителя')],
            'guarantors.*.last_name' => ['required', 'string', 'max:60', CyrillicText::lettersOnly('Фамилията на поръчителя')],
            'guarantors.*.phone' => [
                'required',
                'string',
                'max:30',
                ExclusiveLeadParticipantPhone::forGuarantor([$this->input('phone')]),
            ],
            'guarantors.*.status' => ['required', Rule::in(array_keys(LeadGuarantor::getStatusOptions()))],
        ], $this->publicFormProtectionRules());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge([
            'credit_type.required' => 'Моля, изберете тип кредит.',
            'credit_type.in' => 'Моля, изберете валиден тип кредит.',

            'first_name.required' => 'Моля, въведете вашето име.',
            'first_name.string' => 'Името трябва да бъде текст.',
            'first_name.max' => 'Името не може да бъде по-дълго от 60 символа.',

            'middle_name.string' => 'Презимето трябва да бъде текст.',
            'middle_name.max' => 'Презимето не може да бъде по-дълго от 60 символа.',

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

            'workplace.string' => 'Местоработата трябва да бъде текст.',
            'workplace.max' => 'Местоработата не може да бъде по-дълга от 120 символа.',

            'job_title.string' => 'Длъжността трябва да бъде текст.',
            'job_title.max' => 'Длъжността не може да бъде по-дълга от 120 символа.',

            'salary.integer' => 'Заплатата трябва да бъде цяло число.',
            'salary.min' => 'Заплатата не може да бъде отрицателна.',

            'marital_status.in' => 'Моля, изберете валидно семейно положение.',

            'children_under_18.integer' => 'Броят деца под 18 трябва да бъде цяло число.',
            'children_under_18.min' => 'Броят деца под 18 не може да бъде отрицателен.',

            'salary_bank.string' => 'Банката за заплата трябва да бъде текст.',
            'salary_bank.max' => 'Банката за заплата не може да бъде по-дълга от 120 символа.',

            'amount.required' => 'Моля, изберете желаната сума.',
            'amount.integer' => 'Сумата трябва да бъде цяло число.',
            'amount.min' => 'Сумата трябва да бъде поне 5000.',
            'amount.max' => 'Сумата не може да бъде повече от 50000.',

            'property_type.required_if' => 'Моля, изберете вид на имота.',
            'property_type.in' => 'Моля, изберете валиден вид на имота.',

            'property_location.required_if' => 'Моля, въведете местонахождение на имота.',
            'property_location.string' => 'Местонахождението на имота трябва да бъде текст.',
            'property_location.max' => 'Местонахождението на имота не може да бъде по-дълго от 120 символа.',

            'guarantors.required' => 'Моля, въведете име, фамилия и телефон на поръчителя.',
            'guarantors.array' => 'Поръчителите трябва да бъдат в валиден формат.',
            'guarantors.*.first_name.required' => 'Моля, въведете име на поръчител.',
            'guarantors.*.first_name.string' => 'Името на поръчителя трябва да бъде текст.',
            'guarantors.*.first_name.max' => 'Името на поръчителя не може да бъде по-дълго от 60 символа.',
            'guarantors.*.last_name.required' => 'Моля, въведете фамилия на поръчител.',
            'guarantors.*.last_name.string' => 'Фамилията на поръчителя трябва да бъде текст.',
            'guarantors.*.last_name.max' => 'Фамилията на поръчителя не може да бъде по-дълга от 60 символа.',
            'guarantors.*.phone.required' => 'Моля, въведете телефон на поръчител.',
            'guarantors.*.phone.string' => 'Телефонът на поръчителя трябва да бъде текст.',
            'guarantors.*.phone.max' => 'Телефонът на поръчителя не може да бъде по-дълъг от 30 символа.',
            'guarantors.*.status.required' => 'Моля, изберете статус на поръчител.',
            'guarantors.*.status.in' => 'Моля, изберете валиден статус на поръчител.',
            'privacy_consent.accepted' => 'За да изпратите заявката, трябва да се съгласите с обработването на личните данни.',
        ], $this->publicFormProtectionMessages());
    }

    private function normalizeGuarantors(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        return array_map(function (mixed $guarantor): mixed {
            if (! is_array($guarantor)) {
                return $guarantor;
            }

            return array_merge($guarantor, [
                'first_name' => $this->normalizeString($guarantor['first_name'] ?? null),
                'last_name' => $this->normalizeString($guarantor['last_name'] ?? null),
                'phone' => $this->normalizePhone($guarantor['phone'] ?? null),
                'status' => $this->normalizeString($guarantor['status'] ?? null),
            ]);
        }, array_values($value));
    }

    private function isConsumerWithGuarantorLead(): bool
    {
        return $this->input('credit_type') === Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR;
    }

    private function findRecentLeadByPhone(string $phone): ?Lead
    {
        return Lead::query()
            ->forNormalizedPhone($phone)
            ->where('created_at', '>', now()->subDays(14))
            ->latest('created_at')
            ->first(['id', 'created_at']);
    }

    private function duplicateLeadMessage(Lead $lead): string
    {
        $eligibleAt = $lead->created_at->copy()->addDays(14);
        $remainingSeconds = max(1, $eligibleAt->getTimestamp() - now()->getTimestamp());
        $remainingDays = (int) ceil($remainingSeconds / 86400);
        $dayLabel = $remainingDays === 1 ? 'ден' : 'дни';

        return "Вече има подадена заявка с този телефонен номер. Може да кандидатствате отново след {$remainingDays} {$dayLabel}.";
    }
}
