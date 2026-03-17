<?php

namespace App\Filament\Resources\AdminDocuments\Pages;

use App\Filament\Resources\AdminDocuments\AdminDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminDocument extends EditRecord
{
    protected static string $resource = AdminDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->syncStoredFileMetadata();
    }
}
