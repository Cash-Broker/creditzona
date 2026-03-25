<?php

namespace App\Filament\Resources\AttachedContactMessages\Pages;

use App\Filament\Resources\AttachedContactMessages\AttachedContactMessageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAttachedContactMessage extends ViewRecord
{
    protected static string $resource = AttachedContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Resources\ContactMessages\ContactMessageResource::makeCreateLeadAction(),
            \App\Filament\Resources\ContactMessages\ContactMessageResource::makeArchiveAction(),
        ];
    }
}
