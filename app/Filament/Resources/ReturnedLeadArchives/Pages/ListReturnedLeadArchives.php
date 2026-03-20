<?php

namespace App\Filament\Resources\ReturnedLeadArchives\Pages;

use App\Filament\Resources\ReturnedLeadArchives\ReturnedLeadArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListReturnedLeadArchives extends ListRecords
{
    protected static string $resource = ReturnedLeadArchiveResource::class;

    protected static ?string $title = 'Архивирани върнати';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
