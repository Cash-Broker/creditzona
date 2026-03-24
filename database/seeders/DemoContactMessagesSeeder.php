<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoContactMessagesSeeder extends Seeder
{
    public function run(): void
    {
        $staff = $this->resolveDemoStaff();

        ContactMessage::query()
            ->whereIn('email', [
                'demo.contact.1@creditzona.test',
                'demo.contact.2@creditzona.test',
                'demo.contact.3@creditzona.test',
                'demo.contact.4@creditzona.test',
                'demo.contact.5@creditzona.test',
            ])
            ->delete();

        $this->createMessage([
            'full_name' => 'Мартин Георгиев',
            'phone' => '0888300001',
            'email' => 'demo.contact.1@creditzona.test',
            'message' => 'Интересувам се от разговор за финансиране с поръчител. Удобно е след 17:30.',
        ], '2026-03-24 09:20:00');

        $this->createMessage([
            'full_name' => 'Теодора Пенева',
            'phone' => '0888300002',
            'email' => 'demo.contact.2@creditzona.test',
            'message' => 'Моля за обратна връзка. Има вече отказ от банка и искам консултация.',
            'assigned_user_id' => $staff['anna']->id,
        ], '2026-03-24 10:15:00');

        $this->createMessage([
            'full_name' => 'Стоян Димитров',
            'phone' => '0888300003',
            'email' => 'demo.contact.3@creditzona.test',
            'message' => 'Поръчителят ми е с постоянен договор. Може ли да ме насочите какви документи трябват?',
            'assigned_user_id' => $staff['elena']->id,
        ], '2026-03-24 10:55:00');

        $this->createMessage([
            'full_name' => 'Ива Колева',
            'phone' => '0888300004',
            'email' => 'demo.contact.4@creditzona.test',
            'message' => 'Съобщението е обработено и е преместено в архив за тест на новата папка.',
            'assigned_user_id' => $staff['krasimira']->id,
            'archived_by_user_id' => $staff['renata']->id,
            'archived_at' => CarbonImmutable::parse('2026-03-24 11:20:00', 'Europe/Sofia'),
        ], '2026-03-24 11:00:00');

        $this->createMessage([
            'full_name' => 'Радослав Тодоров',
            'phone' => '0888300005',
            'email' => 'demo.contact.5@creditzona.test',
            'message' => 'Архивирано необвързано съобщение, за да се вижда и този случай в архива.',
            'assigned_user_id' => null,
            'archived_by_user_id' => $staff['renata']->id,
            'archived_at' => CarbonImmutable::parse('2026-03-24 12:10:00', 'Europe/Sofia'),
        ], '2026-03-24 11:50:00');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMessage(array $attributes, string $createdAt): ContactMessage
    {
        $message = ContactMessage::query()->create($attributes);

        $timestamp = CarbonImmutable::parse($createdAt, 'Europe/Sofia');

        $message->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $attributes['archived_at'] ?? $timestamp,
        ])->saveQuietly();

        return $message;
    }

    /**
     * @return array{renata: User, anna: User, elena: User, krasimira: User}
     */
    private function resolveDemoStaff(): array
    {
        return [
            'renata' => $this->resolveUser('Рената', ['renata@creditzona.bg', 'renata@creditzona.test']),
            'anna' => $this->resolveUser('Анна', ['anna@creditzona.bg', 'anna@creditzona.test']),
            'elena' => $this->resolveUser('Елена', ['elena@creditzona.bg', 'elena@creditzona.test']),
            'krasimira' => $this->resolveUser('Красимира', ['krasimira@creditzona.bg', 'krasimira@creditzona.test']),
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
