<?php

namespace Tests\Feature;

use App\Filament\Resources\ArchivedContactMessages\ArchivedContactMessageResource;
use App\Filament\Resources\AttachedContactMessageArchives\AttachedContactMessageArchiveResource;
use App\Filament\Resources\AttachedContactMessages\AttachedContactMessageResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\ContactMessageService;
use Illuminate\Auth\Access\AuthorizationException;
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

        $this->assertSame($admin->id, $message->admin_archived_by_user_id);
        $this->assertNotNull($message->admin_archived_at);
        $this->assertNull($message->archived_at);
        $this->assertNull($message->archived_by_user_id);
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

    public function test_admin_archived_contact_message_remains_visible_to_operator(): void
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

        $this->assertSame([
            $message->id,
        ], AttachedContactMessageResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_operator_can_archive_own_attached_contact_message_into_personal_archive(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $message = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
        ]));

        app(ContactMessageService::class)->archiveMessage($message, $operator);

        $message->refresh();

        $this->assertSame($operator->id, $message->archived_by_user_id);
        $this->assertNotNull($message->archived_at);

        $this->actingAs($operator);

        $this->assertSame([], AttachedContactMessageResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([
            $message->id,
        ], AttachedContactMessageArchiveResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_operator_cannot_archive_contact_message_assigned_to_someone_else(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $message = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $elena->id,
        ]));

        $this->expectException(AuthorizationException::class);

        app(ContactMessageService::class)->archiveMessage($message, $anna);
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
