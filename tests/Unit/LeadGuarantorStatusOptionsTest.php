<?php

namespace Tests\Unit;

use App\Models\LeadGuarantor;
use PHPUnit\Framework\TestCase;

class LeadGuarantorStatusOptionsTest extends TestCase
{
    public function test_guarantor_status_options_match_allowed_values(): void
    {
        $this->assertSame([
            LeadGuarantor::STATUS_SUITABLE => 'Годен',
            LeadGuarantor::STATUS_UNSUITABLE => 'Негоден',
            LeadGuarantor::STATUS_DECLINED => 'Отказал се',
        ], LeadGuarantor::getStatusOptions());
    }

    public function test_guarantor_status_label_returns_human_readable_value(): void
    {
        $this->assertSame('Годен', LeadGuarantor::getStatusLabel(LeadGuarantor::STATUS_SUITABLE));
        $this->assertSame('Няма', LeadGuarantor::getStatusLabel(null));
    }

    public function test_guarantor_visual_state_classes_follow_status(): void
    {
        $this->assertSame('attention', LeadGuarantor::getVisualStateKey(null));
        $this->assertSame('suitable', LeadGuarantor::getVisualStateKey(LeadGuarantor::STATUS_SUITABLE));
        $this->assertSame('unsuitable', LeadGuarantor::getVisualStateKey(LeadGuarantor::STATUS_UNSUITABLE));
        $this->assertSame('declined', LeadGuarantor::getVisualStateKey(LeadGuarantor::STATUS_DECLINED));

        $this->assertStringContainsString(
            'lead-guarantor-surface--attention',
            LeadGuarantor::getSurfaceClasses(null),
        );
        $this->assertStringContainsString(
            'lead-guarantor-surface--suitable',
            LeadGuarantor::getSurfaceClasses(LeadGuarantor::STATUS_SUITABLE),
        );
        $this->assertStringContainsString(
            'lead-guarantor-item-label--unsuitable',
            LeadGuarantor::getItemLabelClasses(LeadGuarantor::STATUS_UNSUITABLE),
        );
        $this->assertStringContainsString(
            'lead-guarantor-item-label--declined',
            LeadGuarantor::getItemLabelClasses(LeadGuarantor::STATUS_DECLINED),
        );
    }
}
