<?php

namespace App\Filament\Resources\ContractBatches\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Models\ContractBatch;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContractBatch extends ViewRecord
{
    protected static string $resource = ContractBatchResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            EditAction::make(),
        ];

        if ($this->record instanceof ContractBatch && $this->record->archiveExists()) {
            $actions[] = Action::make('downloadArchive')
                ->label('Свали пакет')
                ->url(route('admin.contract-batches.archive.download', $this->record));
        }

        return $actions;
    }
}
