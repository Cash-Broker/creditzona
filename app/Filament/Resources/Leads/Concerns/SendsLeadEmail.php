<?php

namespace App\Filament\Resources\Leads\Concerns;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;

trait SendsLeadEmail
{
    public function composeLeadEmailAction(): Action
    {
        return Action::make('composeLeadEmail')
            ->label('Изпрати имейл към клиента')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('info')
            ->visible(function (): bool {
                $record = $this->getRecord();

                return $record instanceof Lead && filled($record->email);
            })
            ->modalHeading(function (): string {
                $record = $this->getRecord();
                $email = $record instanceof Lead ? $record->email : null;

                return $email !== null
                    ? sprintf('Изпрати имейл до %s', $email)
                    : 'Изпрати имейл';
            })
            ->modalSubmitActionLabel('Изпрати')
            ->modalCancelActionLabel('Откажи')
            ->schema([
                Textarea::make('email_body')
                    ->label('Съобщение')
                    ->required()
                    ->rows(10)
                    ->placeholder('Напишете съобщението си към клиента...'),
            ])
            ->action(function (array $data): void {
                $this->dispatchLeadEmail($data['email_body'] ?? '');
            });
    }

    public function dispatchLeadEmail(string $body): void
    {
        $user = auth()->user();
        $record = $this->getRecord();

        if (! $user instanceof User || ! $record instanceof Lead) {
            return;
        }

        try {
            app(LeadService::class)->sendEmailToLead($record, $user, $body);
        } catch (AuthorizationException|DomainException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Имейлът е изпратен.')
            ->success()
            ->send();
    }
}
