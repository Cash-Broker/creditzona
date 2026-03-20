<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use App\Filament\Resources\Leads\Widgets\LeadCommunicationWidget;
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

    protected function getFooterWidgets(): array
    {
        return [
            LeadCommunicationWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getWidgetData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }
}
