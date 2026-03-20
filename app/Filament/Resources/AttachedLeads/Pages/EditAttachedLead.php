<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttachedLead extends EditRecord
{
    protected static string $resource = AttachedLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            AttachedLeadResource::makeReturnToPrimaryAction(),
        ];
    }
}
