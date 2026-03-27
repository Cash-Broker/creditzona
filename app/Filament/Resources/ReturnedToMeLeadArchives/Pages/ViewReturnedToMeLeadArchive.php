<?php

namespace App\Filament\Resources\ReturnedToMeLeadArchives\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Filament\Resources\ReturnedToMeLeadArchives\ReturnedToMeLeadArchiveResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnedToMeLeadArchive extends ViewRecord
{
    protected static string $resource = ReturnedToMeLeadArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редакция'),
            Action::make('generateContracts')
                ->label('Генерирай договори')
                ->url(fn (): string => ContractBatchResource::getUrl('create').'?lead_id='.$this->getRecord()->getKey()),
        ];
    }
}
