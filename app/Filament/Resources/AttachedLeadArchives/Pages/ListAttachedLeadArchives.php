<?php

namespace App\Filament\Resources\AttachedLeadArchives\Pages;

use App\Filament\Resources\AttachedLeadArchives\AttachedLeadArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListAttachedLeadArchives extends ListRecords
{
    protected static string $resource = AttachedLeadArchiveResource::class;

    protected static ?string $title = 'Архивирани към мен';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
