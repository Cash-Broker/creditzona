<?php

namespace Tests\Unit;

use App\Filament\Resources\Leads\LeadResource;
use PHPUnit\Framework\TestCase;

class LeadStatusOptionsTest extends TestCase
{
    public function test_lead_status_options_include_all_admin_states(): void
    {
        $this->assertSame([
            'new' => 'Нова',
            'in_progress' => 'В обработка',
            'processed' => 'Обработена',
            'approved' => 'Одобрена',
            'rejected' => 'Отказана',
        ], LeadResource::getStatusOptions());
    }

    public function test_lead_status_label_returns_human_readable_value(): void
    {
        $this->assertSame('Одобрена', LeadResource::getStatusLabel('approved'));
        $this->assertSame('Отказана', LeadResource::getStatusLabel('rejected'));
    }
}
