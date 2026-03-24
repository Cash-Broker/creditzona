<?php

namespace App\Filament\Resources\ReturnedLeadArchives\Pages;

use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\ReturnedLeadArchives\ReturnedLeadArchiveResource;
use App\Models\User;
use App\Services\LeadService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnedLeadArchive extends EditRecord
{
    protected static string $resource = ReturnedLeadArchiveResource::class;

    protected ?int $previousAdditionalUserId = null;

    protected ?string $leadNoteDraft = null;

    /**
     * @var array<int, array{id: int|null, fingerprint: string, note: string}>
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
            ViewAction::make(),
        ];
    }

    public function saveAndRedirect(): void
    {
        $this->save(shouldRedirect: true);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->submit(null)
            ->action('saveAndRedirect');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LeadForm::mutateSubmittedData($data);
    }

    protected function beforeSave(): void
    {
        $rawState = $this->form->getRawState();

        $this->leadNoteDraft = is_array($rawState)
            ? LeadForm::captureLeadNoteDraft($rawState)
            : null;

        $this->guarantorNoteDrafts = is_array($rawState)
            ? LeadForm::captureGuarantorNoteDrafts($rawState)
            : [];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord()->refresh();

        LeadForm::persistLeadNoteDraft($record, $this->leadNoteDraft);
        LeadForm::persistGuarantorNoteDrafts($record, $this->guarantorNoteDrafts);

        $actor = auth()->user();

        app(LeadService::class)->sendAdditionalAssignmentNotification(
            $record->refresh(),
            $this->previousAdditionalUserId,
            $actor instanceof User ? $actor : null,
        );

        $this->previousAdditionalUserId = $record->additional_user_id;
        $this->leadNoteDraft = null;
        $this->guarantorNoteDrafts = [];
    }

    protected function getRedirectUrl(): ?string
    {
        return ReturnedLeadArchiveResource::getUrl('index');
    }
}
