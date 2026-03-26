<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Models\User;
use App\Services\LeadService;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;

class EditAttachedLead extends EditRecord
{
    protected static string $resource = AttachedLeadResource::class;

    protected ?int $previousAdditionalUserId = null;

    protected ?string $leadNoteDraft = null;

    /**
     * @var array<int, array{id: int|null, fingerprint: string, note: string}>
     */
    protected array $guarantorNoteDrafts = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->previousAdditionalUserId = $this->getRecord()->additional_user_id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateContracts')
                ->label('Генерирай договори')
                ->url(fn (): string => ContractBatchResource::getUrl('create').'?lead_id='.$this->getRecord()->getKey()),
        ];
    }

    public function saveAndRedirect(): void
    {
        $this->save(shouldRedirect: true);
    }

    public function saveAndReturn(): void
    {
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        try {
            app(LeadService::class)->returnAttachedLeadToPrimary($this->getRecord()->refresh(), $user);
        } catch (AuthorizationException|DomainException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Заявката е запазена и върната към основния служител.')
            ->success()
            ->send();

        $this->redirect(AttachedLeadResource::getUrl('index'));
    }

    public function getFormActionsContentComponent(): Component
    {
        return Grid::make([
            'default' => 1,
            'md' => 2,
        ])
            ->schema([
                SchemaActions::make([
                    $this->getSaveAndReturnFormAction(),
                    $this->getCancelFormAction(),
                ])
                    ->alignment(Alignment::Start),
                SchemaActions::make([
                    $this->getSaveFormAction(),
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

    protected function getSaveAndReturnFormAction(): Action
    {
        return Action::make('save_and_return')
            ->label('Запази и върни')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Запази и върни')
            ->modalDescription('Промените ще бъдат запазени и заявката ще бъде върната към основния служител.')
            ->action('saveAndReturn');
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

    protected function beforeSave(): void
    {
        $rawState = $this->form->getRawState();

        $this->leadNoteDraft = is_array($rawState)
            ? LeadForm::captureLeadNoteDraft($rawState)
            : null;

        $this->guarantorNoteDrafts = is_array($rawState)
            ? LeadForm::captureGuarantorNoteDrafts($rawState)
            : [];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord()->refresh();

        LeadForm::persistLeadNoteDraft($record, $this->leadNoteDraft);
        LeadForm::persistGuarantorNoteDrafts($record, $this->guarantorNoteDrafts);

        $actor = auth()->user();

        app(LeadService::class)->sendAdditionalAssignmentNotification(
            $record->refresh(),
            $this->previousAdditionalUserId,
            $actor instanceof User ? $actor : null,
        );

        $this->previousAdditionalUserId = $record->additional_user_id;
        $this->leadNoteDraft = null;
        $this->guarantorNoteDrafts = [];
    }

    protected function getRedirectUrl(): ?string
    {
        return AttachedLeadResource::getUrl('index');
    }
}
