<?php

namespace App\Filament\Resources\AttachedContractBatches\Pages;

use App\Filament\Resources\AttachedContractBatches\AttachedContractBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListAttachedContractBatches extends ListRecords
{
    protected static string $resource = AttachedContractBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
