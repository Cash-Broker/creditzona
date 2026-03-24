<?php

namespace App\Filament\Resources\ArchivedContactMessages\Pages;

use App\Filament\Resources\ArchivedContactMessages\ArchivedContactMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListArchivedContactMessages extends ListRecords
{
    protected static string $resource = ArchivedContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
