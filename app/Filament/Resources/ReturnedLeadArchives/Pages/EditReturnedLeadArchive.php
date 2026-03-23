<?php

namespace App\Filament\Resources\ReturnedLeadArchives\Pages;

use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\ReturnedLeadArchives\ReturnedLeadArchiveResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnedLeadArchive extends EditRecord
{
    protected static string $resource = ReturnedLeadArchiveResource::class;

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

    protected function getRedirectUrl(): ?string
    {
        return ReturnedLeadArchiveResource::getUrl('index');
    }
}
