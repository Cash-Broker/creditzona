<?php

namespace App\Filament\Resources\ReturnedToMeLeadArchives\Pages;

use App\Filament\Resources\ReturnedToMeLeadArchives\ReturnedToMeLeadArchiveResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnedToMeLeadArchive extends ViewRecord
{
    protected static string $resource = ReturnedToMeLeadArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редакция'),
        ];
    }
}
