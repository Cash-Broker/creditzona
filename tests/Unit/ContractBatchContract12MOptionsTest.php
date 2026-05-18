<?php

namespace Tests\Unit;

use App\Models\ContractBatch;
use PHPUnit\Framework\TestCase;

class ContractBatchContract12MOptionsTest extends TestCase
{
    public function test_layout_options_include_contract_12m(): void
    {
        $options = ContractBatch::getLayoutOptions();

        $this->assertArrayHasKey(ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M, $options);
        $this->assertSame('Договор 12м', $options[ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M]);
    }

    public function test_document_type_options_include_new_types(): void
    {
        $options = ContractBatch::getDocumentTypeOptions();

        $this->assertArrayHasKey(ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M, $options);
        $this->assertArrayHasKey(ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION, $options);
    }

    public function test_contract_12m_layout_returns_eight_types_in_lawyer_order(): void
    {
        $types = ContractBatch::getDocumentTypesForLayout(ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M);

        $this->assertSame([
            ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
            ContractBatch::DOCUMENT_TYPE_DECLARATION,
        ], $types);
    }

    public function test_order_selected_document_types_preserves_layout_order_for_contract_12m(): void
    {
        $shuffled = [
            ContractBatch::DOCUMENT_TYPE_DECLARATION,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
        ];

        $ordered = ContractBatch::orderSelectedDocumentTypes(
            $shuffled,
            ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
        );

        $this->assertSame([
            ContractBatch::DOCUMENT_TYPE_APPLICATION_REQUEST,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M,
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            ContractBatch::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
            ContractBatch::DOCUMENT_TYPE_DECLARATION,
        ], $ordered);
    }

    public function test_credit_history_declaration_label_distinguishes_copies(): void
    {
        $this->assertSame(
            'Декларация (трудова заетост и кредитна история) - Възложител',
            ContractBatch::getGeneratedDocumentLabel(ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION, 1),
        );

        $this->assertSame(
            'Декларация (трудова заетост и кредитна история) - Поръчител',
            ContractBatch::getGeneratedDocumentLabel(ContractBatch::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION, 2),
        );
    }
}
