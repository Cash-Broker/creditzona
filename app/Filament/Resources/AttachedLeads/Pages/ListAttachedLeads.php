<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use Filament\Resources\Pages\ListRecords;

class ListAttachedLeads extends ListRecords
{
    protected static string $resource = AttachedLeadResource::class;

    protected static ?string $title = 'Закачени към мен';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
