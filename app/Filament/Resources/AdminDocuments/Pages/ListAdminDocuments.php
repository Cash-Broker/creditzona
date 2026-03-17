<?php

namespace App\Filament\Resources\AdminDocuments\Pages;

use App\Filament\Resources\AdminDocuments\AdminDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdminDocuments extends ListRecords
{
    protected static string $resource = AdminDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
