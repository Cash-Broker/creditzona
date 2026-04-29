<?php

namespace Tests\Unit;

use App\Filament\Resources\ContractBatches\Pages\EditContractBatch;
use App\Filament\Resources\ContractBatches\Tables\ContractBatchesTable;
use Tests\TestCase;

class ContractBatchDeletionConfirmationTest extends TestCase
{
    public function test_single_contract_batch_delete_action_requires_confirmation(): void
    {
        $action = EditContractBatch::makeDeleteAction();

        $this->assertTrue($action->isConfirmationRequired());
        $this->assertSame('Изтриване на договорен пакет', $action->getModalHeading());
        $this->assertSame(
            'Сигурни ли сте? Договорният пакет и всички генерирани файлове към него ще бъдат изтрити безвъзвратно.',
            $action->getModalDescription(),
        );
        $this->assertSame('Изтрий пакета', $action->getModalSubmitActionLabel());
    }

    public function test_bulk_contract_batch_delete_action_requires_confirmation(): void
    {
        $action = ContractBatchesTable::makeDeleteBulkAction();

        $this->assertTrue($action->isConfirmationRequired());
        $this->assertSame('Изтриване на избраните договорни пакети', $action->getModalHeading());
        $this->assertSame(
            'Сигурни ли сте? Всички избрани договорни пакети и генерираните им файлове ще бъдат изтрити безвъзвратно.',
            $action->getModalDescription(),
        );
        $this->assertSame('Изтрий пакетите', $action->getModalSubmitActionLabel());
    }
}
