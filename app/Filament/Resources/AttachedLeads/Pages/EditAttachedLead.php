<?php

namespace App\Filament\Resources\AttachedLeads\Pages;

use App\Filament\Resources\AttachedLeadArchives\AttachedLeadArchiveResource;
use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
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

    public function saveAndArchive(): void
    {
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        try {
            app(LeadService::class)->archiveAttachedLead($this->getRecord()->refresh(), $user);
        } catch (AuthorizationException|DomainException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Заявката е запазена и архивирана.')
            ->success()
            ->send();

        $this->redirect(AttachedLeadArchiveResource::getUrl('index'));
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
                    $this->getSaveAndArchiveFormAction(),
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

    protected function getSaveAndArchiveFormAction(): Action
    {
        return Action::make('save_and_archive')
            ->label('Запази и архивирай')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Запази и архивирай')
            ->modalDescription('Промените ще бъдат запазени и заявката ще бъде преместена в "Архивирани към мен".')
            ->action('saveAndArchive');
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
