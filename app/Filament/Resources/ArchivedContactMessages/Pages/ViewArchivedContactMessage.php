<?php

namespace App\Filament\Resources\ArchivedContactMessages\Pages;

use App\Filament\Resources\ArchivedContactMessages\ArchivedContactMessageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewArchivedContactMessage extends ViewRecord
{
    protected static string $resource = ArchivedContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Resources\ContactMessages\ContactMessageResource::makeCreateLeadAction(),
        ];
    }
}
