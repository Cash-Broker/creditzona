<?php

namespace App\Filament\Resources\LeadTraffic\Pages;

use App\Filament\Resources\LeadTraffic\LeadTrafficResource;
use Filament\Resources\Pages\ListRecords;

class ListLeadTraffic extends ListRecords
{
    protected static string $resource = LeadTrafficResource::class;

    protected static ?string $title = 'Източник на заявки — IP и устройства';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
