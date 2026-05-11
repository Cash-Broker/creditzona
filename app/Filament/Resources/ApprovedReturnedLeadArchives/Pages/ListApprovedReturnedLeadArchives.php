<?php

namespace App\Filament\Resources\ApprovedReturnedLeadArchives\Pages;

use App\Filament\Resources\ApprovedReturnedLeadArchives\ApprovedReturnedLeadArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListApprovedReturnedLeadArchives extends ListRecords
{
    protected static string $resource = ApprovedReturnedLeadArchiveResource::class;

    protected static ?string $title = 'Архивирани одобрени върнати';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
