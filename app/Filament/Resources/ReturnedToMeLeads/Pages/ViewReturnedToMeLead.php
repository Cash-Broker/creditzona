<?php

namespace App\Filament\Resources\ReturnedToMeLeads\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Filament\Resources\ReturnedToMeLeads\ReturnedToMeLeadResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnedToMeLead extends ViewRecord
{
    protected static string $resource = ReturnedToMeLeadResource::class;

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
