<?php

namespace App\Filament\Resources\ReturnedLeadArchives\Pages;

use App\Filament\Resources\ReturnedLeadArchives\ReturnedLeadArchiveResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnedLeadArchive extends ViewRecord
{
    protected static string $resource = ReturnedLeadArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редакция'),
        ];
    }
}
