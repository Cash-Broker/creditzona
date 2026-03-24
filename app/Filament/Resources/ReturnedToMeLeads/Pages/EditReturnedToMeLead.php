<?php

namespace App\Filament\Resources\ReturnedToMeLeads\Pages;

use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\ReturnedToMeLeadArchives\ReturnedToMeLeadArchiveResource;
use App\Filament\Resources\ReturnedToMeLeads\ReturnedToMeLeadResource;
use App\Models\User;
use App\Services\LeadService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;

class EditReturnedToMeLead extends EditRecord
{
    protected static string $resource = ReturnedToMeLeadResource::class;

    protected ?int $previousAdditionalUserId = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->previousAdditionalUserId = $this->getRecord()->additional_user_id;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    public function saveAndRedirect(): void
    {
        $this->save(shouldRedirect: true);
    }

    public function saveAndArchive(): void
    {
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        try {
            app(LeadService::class)->archiveReturnedToPrimaryLead($this->getRecord()->refresh(), $user);
        } catch (AuthorizationException|DomainException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Заявката е запазена и архивирана.')
            ->success()
            ->send();

        $this->redirect(ReturnedToMeLeadArchiveResource::getUrl('index'));
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->submit(null)
            ->action('saveAndRedirect');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveAndArchiveFormAction(),
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveAndArchiveFormAction(): Action
    {
        return Action::make('save_and_archive')
            ->label('Запази и архивирай')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Запази и архивирай')
            ->modalDescription('Промените ще бъдат запазени и заявката ще бъде преместена в "Архивирани върнати към мен".')
            ->action('saveAndArchive');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LeadForm::mutateSubmittedData($data);
    }

    protected function afterSave(): void
    {
        $actor = auth()->user();

        app(LeadService::class)->sendAdditionalAssignmentNotification(
            $this->getRecord()->refresh(),
            $this->previousAdditionalUserId,
            $actor instanceof User ? $actor : null,
        );

        $this->previousAdditionalUserId = $this->getRecord()->additional_user_id;
    }

    protected function getRedirectUrl(): ?string
    {
        return ReturnedToMeLeadResource::getUrl('index');
    }
}
