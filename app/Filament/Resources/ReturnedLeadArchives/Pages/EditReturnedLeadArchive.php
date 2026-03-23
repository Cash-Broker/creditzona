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
        return ReturnedLeadArchiveResource::getUrl('index');
    }
}
