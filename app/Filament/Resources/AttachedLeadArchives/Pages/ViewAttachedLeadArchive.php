<?php

namespace App\Filament\Resources\AttachedLeadArchives\Pages;

use App\Filament\Resources\AttachedLeadArchives\AttachedLeadArchiveResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttachedLeadArchive extends ViewRecord
{
    protected static string $resource = AttachedLeadArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редакция'),
        ];
    }
}
