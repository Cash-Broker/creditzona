<?php

namespace Tests\Feature;

use App\Filament\Resources\AttachedContactMessages\AttachedContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttachedContactMessageBadgeColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_badge_is_red_for_operator_with_messages_outside_the_resource(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        ContactMessage::query()->create($this->messageData($operator));

        $this->actingAs($operator);

        $this->assertSame('danger', AttachedContactMessageResource::getNavigationBadgeColor());
    }

    public function test_badge_is_primary_when_operator_is_on_the_resource(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        ContactMessage::query()->create($this->messageData($operator));

        $this->actingAs($operator);

        $this->get(AttachedContactMessageResource::getUrl('index'));

        $this->assertSame('primary', AttachedContactMessageResource::getNavigationBadgeColor());
    }

    public function test_badge_has_no_special_color_without_messages(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->actingAs($operator);

        $this->assertNull(AttachedContactMessageResource::getNavigationBadgeColor());
    }

    public function test_badge_has_no_special_color_for_non_operator(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);

        $this->assertNull(AttachedContactMessageResource::getNavigationBadgeColor());
    }

    /**
     * @return array<string, mixed>
     */
    private function messageData(User $operator): array
    {
        return [
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение.',
            'assigned_user_id' => $operator->id,
        ];
    }
}
