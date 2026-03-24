<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoLeadsSeeder extends Seeder
{
    public function run(): void
    {
        $staff = $this->resolveDemoStaff();

        Lead::query()
            ->where('source', 'demo-seeder')
            ->delete();

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Мария',
            'middle_name' => 'Петрова',
            'last_name' => 'Георгиева',
            'egn' => '9203156789',
            'phone' => '0888100001',
            'email' => 'demo.lead.1@creditzona.test',
            'city' => 'София',
            'workplace' => 'Софарма АД',
            'job_title' => 'Лаборант',
            'salary' => 1800,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 1,
            'salary_bank' => 'ДСК',
            'credit_bank' => 'УниКредит Булбанк',
            'internal_notes' => "[24.03.2026 09:10] Анна: Клиентката очаква обаждане след 14:00.\n\n[24.03.2026 09:40] Рената: Има шанс с добър поръчител.",
            'amount' => 16000,
            'status' => 'new',
            'assigned_user_id' => $staff['anna']->id,
            'source' => 'demo-seeder',
            'privacy_consent_accepted' => true,
            'privacy_consent_accepted_at' => CarbonImmutable::parse('2026-03-24 09:05:00', 'Europe/Sofia'),
            'privacy_consent_document_name' => Lead::getPrivacyConsentDocumentName(),
            'privacy_consent_document_path' => Lead::getPrivacyConsentDocumentPath(),
        ], [
            [
                'first_name' => 'Петър',
                'middle_name' => 'Иванов',
                'last_name' => 'Маринов',
                'egn' => '8804123456',
                'phone' => '0889200001',
                'email' => 'petar.guarantor.1@creditzona.test',
                'city' => 'София',
                'workplace' => 'Техно Сървис',
                'job_title' => 'Техник',
                'salary' => 2300,
                'marital_status' => Lead::MARITAL_STATUS_MARRIED,
                'children_under_18' => 1,
                'salary_bank' => 'Пощенска банка',
                'credit_bank' => 'Няма',
                'amount' => 16000,
                'internal_notes' => '[24.03.2026 10:00] Анна: Поръчителят е спокоен и съдейства.',
                'status' => LeadGuarantor::STATUS_SUITABLE,
            ],
        ], '2026-03-24 09:00:00');

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Георги',
            'middle_name' => 'Ангелов',
            'last_name' => 'Стоянов',
            'egn' => '8507284561',
            'phone' => '0888100002',
            'email' => 'demo.lead.2@creditzona.test',
            'city' => 'Пловдив',
            'workplace' => 'Мега Трейд',
            'job_title' => 'Търговски представител',
            'salary' => 2100,
            'marital_status' => Lead::MARITAL_STATUS_SINGLE,
            'children_under_18' => 0,
            'salary_bank' => 'ОББ',
            'credit_bank' => 'Няма',
            'internal_notes' => '[24.03.2026 10:10] Елена: Чакам обратна връзка от Рената.',
            'amount' => 22000,
            'status' => 'in_progress',
            'assigned_user_id' => $staff['elena']->id,
            'additional_user_id' => $staff['renata']->id,
            'source' => 'demo-seeder',
            'privacy_consent_accepted' => true,
            'privacy_consent_accepted_at' => CarbonImmutable::parse('2026-03-24 10:00:00', 'Europe/Sofia'),
            'privacy_consent_document_name' => Lead::getPrivacyConsentDocumentName(),
            'privacy_consent_document_path' => Lead::getPrivacyConsentDocumentPath(),
        ], [
            [
                'first_name' => 'Иван',
                'middle_name' => 'Колев',
                'last_name' => 'Тодоров',
                'egn' => '9105056781',
                'phone' => '0889200002',
                'email' => 'petar.guarantor.2@creditzona.test',
                'city' => 'Пловдив',
                'workplace' => 'Транс Лоджистик',
                'job_title' => 'Шофьор',
                'salary' => 1700,
                'marital_status' => Lead::MARITAL_STATUS_MARRIED,
                'children_under_18' => 2,
                'salary_bank' => 'ДСК',
                'credit_bank' => 'Няма',
                'amount' => 22000,
                'internal_notes' => '[24.03.2026 10:30] Рената: Да се провери стар просрочен кредит.',
                'status' => LeadGuarantor::STATUS_UNSUITABLE,
            ],
        ], '2026-03-24 10:00:00');

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Николай',
            'middle_name' => 'Димитров',
            'last_name' => 'Илиев',
            'egn' => '9009113344',
            'phone' => '0888100003',
            'email' => 'demo.lead.3@creditzona.test',
            'city' => 'Варна',
            'workplace' => 'Строй Ком',
            'job_title' => 'Складов работник',
            'salary' => 1500,
            'marital_status' => Lead::MARITAL_STATUS_SINGLE,
            'children_under_18' => 0,
            'salary_bank' => 'Райфайзенбанк',
            'credit_bank' => 'Няма',
            'internal_notes' => '[24.03.2026 08:45] Красимира: Пратен е SMS с нужните документи.',
            'amount' => 9000,
            'status' => 'sms',
            'assigned_user_id' => $staff['krasimira']->id,
            'source' => 'demo-seeder',
        ], [], '2026-03-24 08:40:00');

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Силвия',
            'middle_name' => 'Йорданова',
            'last_name' => 'Иванова',
            'egn' => '9406019876',
            'phone' => '0888100004',
            'email' => 'demo.lead.4@creditzona.test',
            'city' => 'Бургас',
            'workplace' => 'Маркет Плюс',
            'job_title' => 'Касиер',
            'salary' => 1450,
            'marital_status' => Lead::MARITAL_STATUS_COHABITING,
            'children_under_18' => 1,
            'salary_bank' => 'ПИБ',
            'credit_bank' => 'Няма',
            'internal_notes' => '[23.03.2026 17:20] Анна: Да се потърси отново утре сутрин.',
            'amount' => 12000,
            'status' => 'email',
            'assigned_user_id' => $staff['anna']->id,
            'marked_for_later_at' => CarbonImmutable::parse('2026-03-24 07:30:00', 'Europe/Sofia'),
            'source' => 'demo-seeder',
        ], [], '2026-03-23 17:00:00');

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Антон',
            'middle_name' => 'Колев',
            'last_name' => 'Маринов',
            'egn' => '8702146688',
            'phone' => '0888100005',
            'email' => 'demo.lead.5@creditzona.test',
            'city' => 'Русе',
            'workplace' => 'Еко Пласт',
            'job_title' => 'Оператор машина',
            'salary' => 1950,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 2,
            'salary_bank' => 'Банка ДСК',
            'credit_bank' => 'Няма',
            'internal_notes' => '[23.03.2026 11:10] Рената: Връщам към Елена за дообработка.',
            'amount' => 25000,
            'status' => 'processed',
            'assigned_user_id' => $staff['elena']->id,
            'returned_additional_user_id' => $staff['renata']->id,
            'returned_to_primary_at' => CarbonImmutable::parse('2026-03-23 11:10:00', 'Europe/Sofia'),
            'source' => 'demo-seeder',
        ], [], '2026-03-23 10:30:00');

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Росица',
            'middle_name' => 'Димитрова',
            'last_name' => 'Пенева',
            'egn' => '8904087744',
            'phone' => '0888100006',
            'email' => 'demo.lead.6@creditzona.test',
            'city' => 'Стара Загора',
            'workplace' => 'Текстил Про',
            'job_title' => 'Шивач',
            'salary' => 1680,
            'marital_status' => Lead::MARITAL_STATUS_DIVORCED,
            'children_under_18' => 1,
            'salary_bank' => 'ОББ',
            'credit_bank' => 'Няма',
            'internal_notes' => "[22.03.2026 15:00] Елена: Върната към Анна.\n\n[23.03.2026 10:20] Анна: Архивирана след разговор.",
            'amount' => 14000,
            'status' => 'approved',
            'assigned_user_id' => $staff['anna']->id,
            'returned_additional_user_id' => $staff['elena']->id,
            'returned_to_primary_at' => CarbonImmutable::parse('2026-03-22 15:00:00', 'Europe/Sofia'),
            'returned_to_primary_archived_user_id' => $staff['anna']->id,
            'returned_to_primary_archived_at' => CarbonImmutable::parse('2026-03-23 10:20:00', 'Europe/Sofia'),
            'source' => 'demo-seeder',
        ], [], '2026-03-22 14:00:00');

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Петър',
            'middle_name' => 'Иванов',
            'last_name' => 'Николов',
            'egn' => '8309122233',
            'phone' => '0888100007',
            'email' => 'demo.lead.7@creditzona.test',
            'city' => 'Плевен',
            'workplace' => 'Авто Сервиз',
            'job_title' => 'Монтьор',
            'salary' => 1750,
            'marital_status' => Lead::MARITAL_STATUS_SINGLE,
            'children_under_18' => 0,
            'salary_bank' => 'Пощенска банка',
            'credit_bank' => 'Няма',
            'internal_notes' => '[21.03.2026 12:15] Красимира: Legacy потребителски кредит без поръчител.',
            'amount' => 7000,
            'status' => 'rejected',
            'assigned_user_id' => $staff['krasimira']->id,
            'source' => 'demo-seeder',
        ], [], '2026-03-21 12:00:00');

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_MORTGAGE,
            'first_name' => 'Виктория',
            'middle_name' => 'Александрова',
            'last_name' => 'Маринова',
            'egn' => '9102204433',
            'phone' => '0888100008',
            'email' => 'demo.lead.8@creditzona.test',
            'city' => 'Варна',
            'workplace' => 'Сий Вю Хотел',
            'job_title' => 'Рецепционист',
            'salary' => 2400,
            'marital_status' => Lead::MARITAL_STATUS_MARRIED,
            'children_under_18' => 1,
            'salary_bank' => 'ЦКБ',
            'credit_bank' => 'Няма',
            'internal_notes' => '[20.03.2026 16:00] Анна: Legacy ипотечна заявка за тест на имотните полета.',
            'amount' => 85000,
            'property_type' => 'apartment',
            'property_location' => 'Варна, Левски',
            'status' => 'new',
            'assigned_user_id' => $staff['anna']->id,
            'source' => 'demo-seeder',
        ], [], '2026-03-20 15:30:00');

        $this->createLead([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => 'Десислава',
            'middle_name' => 'Стоянова',
            'last_name' => 'Кирова',
            'egn' => '9301185544',
            'phone' => '0888100009',
            'email' => 'demo.lead.9@creditzona.test',
            'city' => 'Шумен',
            'workplace' => 'Логистик БГ',
            'job_title' => 'Администратор',
            'salary' => 2000,
            'marital_status' => Lead::MARITAL_STATUS_SINGLE,
            'children_under_18' => 0,
            'salary_bank' => 'Алианц Банк',
            'credit_bank' => 'Няма',
            'internal_notes' => '[24.03.2026 11:50] Искра: Активна закачена заявка за тест.',
            'amount' => 19000,
            'status' => 'new',
            'assigned_user_id' => $staff['krasimira']->id,
            'additional_user_id' => $staff['iskra']->id,
            'source' => 'demo-seeder',
        ], [], '2026-03-24 11:40:00');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $guarantors
     */
    private function createLead(array $attributes, array $guarantors, string $createdAt): Lead
    {
        $lead = Lead::query()->create(array_merge([
            'documents' => null,
            'document_file_names' => null,
            'source' => 'demo-seeder',
            'utm_source' => null,
            'utm_campaign' => null,
            'utm_medium' => null,
            'gclid' => null,
            'privacy_consent_accepted' => false,
            'privacy_consent_accepted_at' => null,
            'privacy_consent_document_name' => null,
            'privacy_consent_document_path' => null,
        ], $attributes));

        $timestamp = CarbonImmutable::parse($createdAt, 'Europe/Sofia');

        $lead->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveQuietly();

        foreach ($guarantors as $guarantorAttributes) {
            $guarantor = $lead->guarantors()->create(array_merge([
                'middle_name' => null,
                'email' => null,
                'city' => null,
                'workplace' => null,
                'job_title' => null,
                'salary' => null,
                'marital_status' => null,
                'children_under_18' => null,
                'salary_bank' => null,
                'credit_bank' => null,
                'amount' => null,
                'property_type' => null,
                'property_location' => null,
                'documents' => null,
                'document_file_names' => null,
                'internal_notes' => null,
                'status' => null,
            ], $guarantorAttributes));

            $guarantor->forceFill([
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->saveQuietly();
        }

        return $lead;
    }

    /**
     * @return array{renata: User, anna: User, elena: User, krasimira: User, iskra: User}
     */
    private function resolveDemoStaff(): array
    {
        return [
            'renata' => $this->resolveUser('Рената', ['renata@creditzona.bg', 'renata@creditzona.test']),
            'anna' => $this->resolveUser('Анна', ['anna@creditzona.bg', 'anna@creditzona.test']),
            'elena' => $this->resolveUser('Елена', ['elena@creditzona.bg', 'elena@creditzona.test']),
            'krasimira' => $this->resolveUser('Красимира', ['krasimira@creditzona.bg', 'krasimira@creditzona.test']),
            'iskra' => $this->resolveUser('Искра', ['iskra@creditzona.bg', 'iskra@creditzona.test']),
        ];
    }

    /**
     * @param  array<int, string>  $emails
     */
    private function resolveUser(string $name, array $emails): User
    {
        $user = User::query()
            ->where(function ($query) use ($name, $emails): void {
                $query
                    ->where('name', $name)
                    ->orWhereIn('email', $emails);
            })
            ->first();

        if (! $user instanceof User) {
            throw new RuntimeException("Missing demo staff user for {$name}. Run DemoUsersSeeder first.");
        }

        return $user;
    }
}
