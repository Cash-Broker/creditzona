<?php

namespace Tests\Feature;

use App\Filament\Resources\ArchivedContactMessages\ArchivedContactMessageResource;
use App\Filament\Resources\AttachedContactMessages\AttachedContactMessageResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\ContactMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_archive_contact_message(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $message = ContactMessage::query()->create($this->contactMessageData());

        app(ContactMessageService::class)->archiveMessage($message, $admin);

        $message->refresh();

        $this->assertSame($admin->id, $message->archived_by_user_id);
        $this->assertNotNull($message->archived_at);
    }

    public function test_archived_contact_messages_leave_active_admin_list_and_enter_archive_resource(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $activeMessage = ContactMessage::query()->create($this->contactMessageData([
            'email' => 'active@example.com',
        ]));

        $archivedMessage = ContactMessage::query()->create($this->contactMessageData([
            'email' => 'archived@example.com',
            'phone' => '0888000002',
        ]));

        app(ContactMessageService::class)->archiveMessage($archivedMessage, $admin);

        $this->actingAs($admin);

        $this->assertSame([
            $activeMessage->id,
        ], ContactMessageResource::getEloquentQuery()->pluck('id')->all());

        $this->assertSame([
            $archivedMessage->id,
        ], ArchivedContactMessageResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_archived_contact_message_disappears_from_operator_attached_messages(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $message = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
        ]));

        app(ContactMessageService::class)->archiveMessage($message, $admin);

        $this->actingAs($operator);

        $this->assertSame([], AttachedContactMessageResource::getEloquentQuery()->pluck('id')->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function contactMessageData(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение.',
            'assigned_user_id' => null,
            'archived_by_user_id' => null,
            'archived_at' => null,
        ], $overrides);
    }
}
