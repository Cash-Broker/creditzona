<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Alignment;

class EditAttachedLead extends EditRecord
{
    protected static string $resource = AttachedLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function saveAndRedirect(): void
    {
        $this->save(shouldRedirect: true);
    }

    public function getFormActionsContentComponent(): Component
    {
        return Grid::make([
            'default' => 1,
            'md' => 2,
        ])
            ->schema([
                SchemaActions::make([
                    $this->getSaveFormAction(),
                    $this->getCancelFormAction(),
                ])
                    ->alignment(Alignment::Start),
                SchemaActions::make([
                    AttachedLeadResource::makeReturnToPrimaryAction(),
                ])
                    ->alignment(Alignment::End),
            ])
            ->extraAttributes([
                'class' => 'mt-4',
            ])
            ->key('form-actions');
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->submit(null)
            ->action('saveAndRedirect')
            ->label('Запази');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Отказ');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LeadForm::mutateSubmittedData($data);
    }

    protected function getRedirectUrl(): ?string
    {
        return AttachedLeadResource::getUrl('index');
    }
}
