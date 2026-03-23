<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Models\User;
use App\Services\LeadService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Alignment;

class EditAttachedLead extends EditRecord
{
    protected static string $resource = AttachedLeadResource::class;

    protected ?int $previousAdditionalUserId = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->previousAdditionalUserId = $this->getRecord()->additional_user_id;
    }

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

    protected function afterSave(): void
    {
        $actor = auth()->user();

        app(LeadService::class)->sendAdditionalAssignmentNotification(
            $this->getRecord()->refresh(),
            $this->previousAdditionalUserId,
            $actor instanceof User ? $actor : null,
        );

        $this->previousAdditionalUserId = $this->getRecord()->additional_user_id;
    }

    protected function getRedirectUrl(): ?string
    {
        return AttachedLeadResource::getUrl('index');
    }
}
