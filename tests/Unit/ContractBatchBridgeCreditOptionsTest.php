<?php

namespace Tests\Unit;

use App\Models\ContractBatch;
use PHPUnit\Framework\TestCase;

class ContractBatchBridgeCreditOptionsTest extends TestCase
{
    public function test_layout_options_include_bridge_credit(): void
    {
        $options = ContractBatch::getLayoutOptions();

        $this->assertArrayHasKey(ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT, $options);
        $this->assertSame('Мостов кредит', $options[ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT]);
    }

    public function test_layout_label_resolves_bridge_credit(): void
    {
        $this->assertSame(
            'Мостов кредит',
            ContractBatch::getLayoutLabel(ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT),
        );
    }

    public function test_bridge_credit_layout_generates_the_same_documents_as_full(): void
    {
        $this->assertSame(
            ContractBatch::getDocumentTypesForLayout(ContractBatch::DOCUMENT_LAYOUT_FULL),
            ContractBatch::getDocumentTypesForLayout(ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT),
        );

        $this->assertContains(
            ContractBatch::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ContractBatch::getDocumentTypesForLayout(ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT),
        );

        $this->assertContains(
            ContractBatch::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
            ContractBatch::getDocumentTypesForLayout(ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT),
        );
    }
}
