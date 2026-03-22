<?php

namespace App\Filament\Resources\AttachedContactMessages\Pages;

use App\Filament\Resources\AttachedContactMessages\AttachedContactMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListAttachedContactMessages extends ListRecords
{
    protected static string $resource = AttachedContactMessageResource::class;

    protected static ?string $title = 'Съобщения към мен';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
