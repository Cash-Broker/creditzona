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
}
