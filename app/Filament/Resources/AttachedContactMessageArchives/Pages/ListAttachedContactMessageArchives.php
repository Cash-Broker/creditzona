<?php

namespace App\Filament\Resources\AttachedContactMessageArchives\Pages;

use App\Filament\Resources\AttachedContactMessageArchives\AttachedContactMessageArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListAttachedContactMessageArchives extends ListRecords
{
    protected static string $resource = AttachedContactMessageArchiveResource::class;

    protected static ?string $title = 'Архив на съобщения към мен';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
