<?php

namespace App\Filament\Resources\ReturnedToMeLeadArchives\Pages;

use App\Filament\Resources\ReturnedToMeLeadArchives\ReturnedToMeLeadArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListReturnedToMeLeadArchives extends ListRecords
{
    protected static string $resource = ReturnedToMeLeadArchiveResource::class;

    protected static ?string $title = 'Архивирани върнати към мен';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
