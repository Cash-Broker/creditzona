<?php

namespace App\Filament\Resources\ContractBatches\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContractBatches extends ListRecords
{
    protected static string $resource = ContractBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
