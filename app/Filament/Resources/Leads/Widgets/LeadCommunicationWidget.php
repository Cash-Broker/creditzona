<?php

namespace App\Filament\Resources\Leads\Widgets;

use App\Models\Lead;
use App\Models\LeadMessage;
use App\Models\User;
use App\Services\LeadMessageService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class LeadCommunicationWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.resources.leads.widgets.lead-communication-widget';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public Lead $record;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $messageData = [];

    public function mount(Lead $record): void
    {
        $this->record = $record;
        $this->form->fill([
            'body' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->hiddenLabel()
                    ->placeholder('Напишете вътрешно съобщение по клиента...')
                    ->required()
                    ->maxLength(2000)
                    ->rows(4)
                    ->autosize()
                    ->extraInputAttributes([
                        'class' => 'min-h-28 rounded-2xl bg-gray-50/80 text-sm leading-6 shadow-none dark:bg-gray-900/70',
                    ], merge: true)
                    ->dehydrateStateUsing(static fn (?string $state): ?string => filled($state) ? trim($state) : null),
            ])
            ->statePath('messageData');
    }

    public function send(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $data = $this->form->getState();

        app(LeadMessageService::class)->createMessage($this->record, $user, $data);

        $this->form->fill([
            'body' => null,
        ]);

        Notification::make()
            ->title('Съобщението е добавено.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'messages' => $this->getLeadMessages(),
            'currentUserId' => Auth::id(),
        ];
    }

    /**
     * @return Collection<int, LeadMessage>
     */
    protected function getLeadMessages(): Collection
    {
        return $this->record
            ->messages()
            ->with('author:id,name')
            ->orderBy('created_at')
            ->get();
    }
}
