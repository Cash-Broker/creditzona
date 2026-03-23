<?php

namespace App\Filament\Resources\ReturnedToMeLeads\Pages;

use App\Filament\Resources\ReturnedToMeLeads\ReturnedToMeLeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnedToMeLead extends ViewRecord
{
    protected static string $resource = ReturnedToMeLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редакция'),
        ];
    }
}
