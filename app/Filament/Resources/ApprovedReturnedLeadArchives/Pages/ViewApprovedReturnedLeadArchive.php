<?php

namespace App\Filament\Resources\ApprovedReturnedLeadArchives\Pages;

use App\Filament\Resources\ApprovedReturnedLeadArchives\ApprovedReturnedLeadArchiveResource;
use App\Filament\Resources\ContractBatches\ContractBatchResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApprovedReturnedLeadArchive extends ViewRecord
{
    protected static string $resource = ApprovedReturnedLeadArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редакция'),
            Action::make('generateContracts')
                ->label('Генерирай договори')
                ->url(fn (): string => ContractBatchResource::getUrl('create').'?lead_id='.$this->getRecord()->getKey())
                ->visible(fn (): bool => auth()->user()?->canViewAllContracts() ?? false),
        ];
    }
}
