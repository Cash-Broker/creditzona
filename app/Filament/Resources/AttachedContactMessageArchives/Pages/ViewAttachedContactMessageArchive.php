<?php

namespace App\Filament\Resources\AttachedContactMessageArchives\Pages;

use App\Filament\Resources\AttachedContactMessageArchives\AttachedContactMessageArchiveResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAttachedContactMessageArchive extends ViewRecord
{
    protected static string $resource = AttachedContactMessageArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Resources\ContactMessages\ContactMessageResource::makeCreateLeadAction(),
        ];
    }
}
