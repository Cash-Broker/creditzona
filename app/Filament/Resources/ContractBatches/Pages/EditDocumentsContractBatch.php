<?php

namespace App\Filament\Resources\ContractBatches\Pages;

use App\Filament\Resources\ContractBatches\ContractBatchResource;
use App\Filament\Resources\ContractBatches\Schemas\ContractBatchForm;
use App\Models\ContractBatch;
use App\Models\User;
use App\Services\Contracts\ContractGenerationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class EditDocumentsContractBatch extends EditRecord
{
    protected static string $resource = ContractBatchResource::class;

    protected string $view = 'filament.contract-batches.pages.edit-step-two';

    public function getTitle(): string
    {
        $record = $this->getRecord();

        $date = $record instanceof ContractBatch && $record->request_date
            ? $record->request_date->format('d.m.Y')
            : null;

        return $date !== null ? 'Договор от '.$date : 'Документи';
    }

    public function getMaxWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function form(Schema $schema): Schema
    {
        return ContractBatchForm::configureStepTwo($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditContractBatch::makeDeleteAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getBackFormAction(),
            $this->getSaveFormAction(),
            $this->getDeleteFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Обнови');
    }

    protected function getBackFormAction(): Action
    {
        return Action::make('back')
            ->label('Назад')
            ->color('gray')
            ->url(fn (): string => ContractBatchResource::getUrl('edit', [
                'record' => $this->getRecord(),
            ]));
    }

    protected function getDeleteFormAction(): Action
    {
        return Action::make('deleteFromForm')
            ->label('Изтрий')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Изтриване на договорен пакет')
            ->modalDescription('Сигурни ли сте? Договорният пакет и всички генерирани файлове към него ще бъдат изтрити безвъзвратно.')
            ->modalSubmitActionLabel('Изтрий пакета')
            ->action(function (): void {
                $record = $this->getRecord();
                $record->delete();
                $this->redirect(ContractBatchResource::getUrl('index'));
            });
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof ContractBatch) {
            return $data;
        }

        return $record->getSubmittedInput();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        if ($record instanceof ContractBatch) {
            $data = array_replace_recursive($record->getSubmittedInput(), $data);
        }

        if (empty($data['company_key'])) {
            $data['company_key'] = ContractBatch::COMPANY_REKREDO_KONSULT_DPK;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            throw new RuntimeException('Неуспешно разпознаване на текущия администратор.');
        }

        if (! $record instanceof ContractBatch) {
            throw new RuntimeException('Невалиден договорен пакет за обновяване.');
        }

        return app(ContractGenerationService::class)->updateBatch($record, $data, $actor);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Договорът е генериран успешно')
            ->body('Документите са обновени и са готови за сваляне.');
    }
}
