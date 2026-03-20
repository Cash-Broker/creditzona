<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttachedLead extends ViewRecord
{
    protected static string $resource = AttachedLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редакция'),
        ];
    }
}
