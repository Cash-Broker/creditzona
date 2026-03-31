<?php

namespace App\Filament\Resources\ApprovedReturnedLeads\Pages;

use App\Filament\Resources\ApprovedReturnedLeads\ApprovedReturnedLeadResource;
use App\Filament\Resources\ContractBatches\ContractBatchResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApprovedReturnedLead extends ViewRecord
{
    protected static string $resource = ApprovedReturnedLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('generateContracts')
                ->label('Генерирай договори')
                ->url(fn (): string => ContractBatchResource::getUrl('create').'?lead_id='.$this->getRecord()->getKey()),
        ];
    }
}
