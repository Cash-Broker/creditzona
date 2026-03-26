<?php

namespace App\Filament\Resources\ContractBatches\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Models\ContractBatch;
use App\Models\User;
use App\Services\Contracts\ContractGenerationService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class EditContractBatch extends EditRecord
{
    protected static string $resource = ContractBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->getRecord()->getSubmittedInput();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            throw new RuntimeException('Неуспешно разпознаване на текущия администратор.');
        }

        if (! $record instanceof ContractBatch) {
            throw new RuntimeException('Невалиден договорен пакет за обновяване.');
        }

        return app(ContractGenerationService::class)->updateBatch($record, $data, $actor);
    }
}
