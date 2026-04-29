<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\Widgets\NoteHistoryChatWidget;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NoteHistoryChatWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_note_chat_shows_writing_hint(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $lead = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER,
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'egn' => '9001010000',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'city' => 'Пловдив',
            'amount' => 10000,
            'status' => 'new',
            'assigned_user_id' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(NoteHistoryChatWidget::class, [
            'leadId' => $lead->id,
            'ownerLabel' => 'заявката',
        ])
            ->assertSee('Чатът е активен')
            ->assertSee('Enter изпраща')
            ->assertSee('Shift+Enter добавя нов ред')
            ->assertSee('Чернова - още не е изпратена')
            ->assertSee('Черновата се пази в браузъра')
            ->assertSee('Записано успешно')
            ->assertSee('Не успяхме да запишем');
    }

    public function test_note_chat_without_saved_owner_shows_inactive_hint(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);

        Livewire::test(NoteHistoryChatWidget::class, [
            'ownerLabel' => 'поръчителя',
        ])
            ->assertSee('Запазете първо поръчителя, за да активирате чата.');
    }
}
