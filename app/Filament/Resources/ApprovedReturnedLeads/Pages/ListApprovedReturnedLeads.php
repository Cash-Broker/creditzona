<?php

namespace App\Filament\Resources\ApprovedReturnedLeads\Pages;

use App\Filament\Resources\ApprovedReturnedLeads\ApprovedReturnedLeadResource;
use Filament\Resources\Pages\ListRecords;

class ListApprovedReturnedLeads extends ListRecords
{
    protected static string $resource = ApprovedReturnedLeadResource::class;

    protected static ?string $title = 'Одобрени върнати';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
