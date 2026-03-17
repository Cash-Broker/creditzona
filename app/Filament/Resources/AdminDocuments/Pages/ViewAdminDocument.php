<?php

namespace App\Filament\Resources\AdminDocuments\Pages;

use App\Filament\Resources\AdminDocuments\AdminDocumentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAdminDocument extends ViewRecord
{
    protected static string $resource = AdminDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
