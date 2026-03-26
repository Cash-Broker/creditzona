<?php

namespace App\Filament\Resources\ReturnedToMeLeads\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
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

    /**
     * @var array<int, array{id: int|null, fingerprint: string, existing_notes: ?string, entries: array<int, array<string, mixed>>, note: ?string}>
     */
    protected array $guarantorNoteDrafts = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->previousAdditionalUserId = $this->getRecord()->additional_user_id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateContracts')
                ->label('Генерирай договори')
                ->url(fn (): string => ContractBatchResource::getUrl('create').'?lead_id='.$this->getRecord()->getKey()),
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
        return LeadForm::mutateSubmittedData($data, $this->getRecord());
    }

    protected function beforeSave(): void
    {
        $rawState = $this->form->getRawState();

        $this->guarantorNoteDrafts = is_array($rawState)
            ? LeadForm::captureGuarantorNoteDrafts($rawState)
            : [];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord()->refresh();

        LeadForm::persistGuarantorNoteDrafts($record, $this->guarantorNoteDrafts);

        $actor = auth()->user();

        app(LeadService::class)->sendAdditionalAssignmentNotification(
            $record->refresh(),
            $this->previousAdditionalUserId,
            $actor instanceof User ? $actor : null,
        );

        $this->previousAdditionalUserId = $record->additional_user_id;
        $this->guarantorNoteDrafts = [];
    }

    protected function getRedirectUrl(): ?string
    {
        return ReturnedToMeLeadResource::getUrl('index');
    }
}
