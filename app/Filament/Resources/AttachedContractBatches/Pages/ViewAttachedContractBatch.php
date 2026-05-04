<?php

namespace App\Filament\Resources\AttachedContractBatches\Pages;

use App\Filament\Resources\AttachedContractBatches\AttachedContractBatchResource;
use App\Models\ContractBatch;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAttachedContractBatch extends ViewRecord
{
    protected static string $resource = AttachedContractBatchResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->record instanceof ContractBatch) {
            if ($this->record->combinedPdfExists()) {
                $actions[] = Action::make('downloadCombinedPdf')
                    ->label('Свали PDF')
                    ->url(route('admin.contract-batches.combined-pdf.download', $this->record));
            }

            if ($this->record->archiveExists()) {
                $actions[] = Action::make('downloadArchive')
                    ->label('Свали пакет')
                    ->url(route('admin.contract-batches.archive.download', $this->record));
            }
        }

        return $actions;
    }
}
