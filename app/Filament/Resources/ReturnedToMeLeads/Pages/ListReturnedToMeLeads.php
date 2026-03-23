<?php

namespace App\Filament\Resources\ReturnedToMeLeads\Pages;

use App\Filament\Resources\ReturnedToMeLeads\ReturnedToMeLeadResource;
use Filament\Resources\Pages\ListRecords;

class ListReturnedToMeLeads extends ListRecords
{
    protected static string $resource = ReturnedToMeLeadResource::class;

    protected static ?string $title = 'Върнати към мен';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
