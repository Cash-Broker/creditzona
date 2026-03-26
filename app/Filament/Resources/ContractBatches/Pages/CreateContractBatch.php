<?php

namespace App\Filament\Resources\ContractBatches\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Models\Lead;
use App\Models\User;
use App\Services\Contracts\ContractGenerationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateContractBatch extends CreateRecord
{
    protected static string $resource = ContractBatchResource::class;

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

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            throw new RuntimeException('Неуспешно разпознаване на текущия администратор.');
        }

        return app(ContractGenerationService::class)->createBatch($data, $actor);
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
