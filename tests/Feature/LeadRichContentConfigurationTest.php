<?php

namespace Tests\Feature;

use App\Models\Lead;
use Tests\TestCase;

class LeadRichContentConfigurationTest extends TestCase
{
    public function test_internal_notes_are_registered_as_private_rich_content(): void
    {
        $lead = new Lead([
            'internal_notes' => '<p><strong>Вътрешна бележка</strong> с формат.</p>',
        ]);

        $attribute = $lead->getRichContentAttribute('internal_notes');

        $this->assertNotNull($attribute);
        $this->assertSame('local', $attribute->getFileAttachmentsDiskName());
        $this->assertSame('private', $attribute->getFileAttachmentsVisibility());
        $this->assertTrue($lead->hasRichContentAttribute('internal_notes'));
        $this->assertStringContainsString('Вътрешна бележка', $lead->renderRichContent('internal_notes'));
    }
}
