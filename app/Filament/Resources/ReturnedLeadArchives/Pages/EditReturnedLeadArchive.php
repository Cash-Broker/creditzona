<?php

namespace App\Filament\Resources\ReturnedLeadArchives\Pages;

use App\Filament\Resources\ReturnedLeadArchives\ReturnedLeadArchiveResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnedLeadArchive extends EditRecord
{
    protected static string $resource = ReturnedLeadArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
