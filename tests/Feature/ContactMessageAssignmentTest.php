<?php

namespace Tests\Feature;

use App\Filament\Resources\AttachedContactMessages\AttachedContactMessageResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\ContactMessageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_contact_message_to_operator(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData());

        $assignedMessage = app(ContactMessageService::class)
            ->assignToOperator($contactMessage, $operator, $admin);

        $this->assertSame($operator->id, $assignedMessage->assigned_user_id);
        $this->assertTrue($assignedMessage->relationLoaded('assignedUser'));
        $this->assertSame($operator->id, $assignedMessage->assignedUser?->id);
    }

    public function test_only_admin_can_assign_contact_messages(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData());

        $this->expectException(AuthorizationException::class);

        app(ContactMessageService::class)->assignToOperator($contactMessage, $otherOperator, $operator);
    }

    public function test_contact_message_resource_remains_admin_only(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->actingAs($admin);

        $this->assertTrue(ContactMessageResource::canViewAny());
        $this->assertTrue(ContactMessageResource::canView($contactMessage));

        $this->actingAs($operator);

        $this->assertFalse(ContactMessageResource::canViewAny());
        $this->assertFalse(ContactMessageResource::canView($contactMessage));
        $this->assertTrue(AttachedContactMessageResource::canViewAny());
        $this->assertTrue(AttachedContactMessageResource::canView($contactMessage));
    }

    public function test_attached_contact_message_resource_query_is_scoped_to_current_operator(): void
    {
        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $assignedToAnna = ContactMessage::query()->create($this->contactMessageData([
            'email' => 'anna-message@example.com',
            'assigned_user_id' => $anna->id,
        ]));

        ContactMessage::query()->create($this->contactMessageData([
            'email' => 'elena-message@example.com',
            'phone' => '0888000002',
            'assigned_user_id' => $elena->id,
        ]));

        ContactMessage::query()->create($this->contactMessageData([
            'email' => 'unassigned-message@example.com',
            'phone' => '0888000003',
            'assigned_user_id' => null,
        ]));

        $this->actingAs($anna);

        $this->assertSame([
            $assignedToAnna->id,
        ], AttachedContactMessageResource::getEloquentQuery()->pluck('id')->all());
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
        ], $overrides);
    }
}
