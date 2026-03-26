<?php

namespace App\Filament\Resources\ReturnedLeadArchives\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Filament\Resources\ReturnedLeadArchives\ReturnedLeadArchiveResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnedLeadArchive extends ViewRecord
{
    protected static string $resource = ReturnedLeadArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateContracts')
                ->label('Генерирай договори')
                ->url(fn (): string => ContractBatchResource::getUrl('create').'?lead_id='.$this->getRecord()->getKey()),
            EditAction::make()
                ->label('Редакция'),
        ];
    }
}
