<?php

namespace App\Filament\Resources\ReturnedToMeLeadArchives\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\ReturnedToMeLeadArchives\ReturnedToMeLeadArchiveResource;
use App\Models\User;
use App\Services\LeadService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnedToMeLeadArchive extends EditRecord
{
    protected static string $resource = ReturnedToMeLeadArchiveResource::class;

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

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->submit(null)
            ->action('saveAndRedirect');
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
        return ReturnedToMeLeadArchiveResource::getUrl('index');
    }
}
