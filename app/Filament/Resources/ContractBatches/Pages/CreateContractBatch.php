<?php

namespace App\Filament\Resources\ContractBatches\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Filament\Resources\ContractBatches\Schemas\ContractBatchForm;
use App\Models\ContractBatch;
use App\Models\Lead;
use App\Models\User;
use App\Services\Contracts\ContractGenerationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateContractBatch extends CreateRecord
{
    protected static string $resource = ContractBatchResource::class;

    protected string $view = 'filament.contract-batches.pages.create';

    public function getMaxWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function form(Schema $schema): Schema
    {
        return ContractBatchForm::configureStepOne($schema);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Към Документи')
            ->color('info');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->visible(false);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Отказ');
    }

    protected function getRedirectUrl(): string
    {
        return ContractBatchResource::getUrl('edit-documents', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill();

        $prefill = $this->getLeadPrefill();

        if ($prefill !== []) {
            $rawState = $this->form->getRawState();

            $this->form->fill(array_replace_recursive(
                is_array($rawState) ? $rawState : [],
                $prefill,
            ));
        }

        $this->callHook('afterFill');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_key'])) {
            $data['company_key'] = ContractBatch::COMPANY_REKREDO_KONSULT_DPK;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            throw new RuntimeException('Неуспешно разпознаване на текущия администратор.');
        }

        return app(ContractGenerationService::class)->saveDraftBatch($data, $actor);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Договорът е създаден')
            ->body('Продължете към документите, за да генерирате PDF и Word файловете.');
    }

    /**
     * @return array<string, mixed>
     */
    private function getLeadPrefill(): array
    {
        $lead = $this->resolveSourceLead();

        if (! $lead instanceof Lead) {
            return [];
        }

        return app(ContractGenerationService::class)
            ->buildFormPrefillFromLead($lead);
    }

    private function resolveSourceLead(): ?Lead
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return null;
        }

        $leadId = request()->integer('lead_id');

        if ($leadId < 1) {
            return null;
        }

        return Lead::query()
            ->visibleToUser($actor)
            ->with('guarantors')
            ->find($leadId);
    }
}
