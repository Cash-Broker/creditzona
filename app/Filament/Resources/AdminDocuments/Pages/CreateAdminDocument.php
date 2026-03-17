<?php

namespace App\Filament\Resources\AdminDocuments\Pages;

use App\Filament\Resources\AdminDocuments\AdminDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminDocument extends CreateRecord
{
    protected static string $resource = AdminDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by_user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncStoredFileMetadata();
    }
}
