<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use App\Filament\Resources\ContractBatches\ContractBatchResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttachedLead extends ViewRecord
{
    protected static string $resource = AttachedLeadResource::class;

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
