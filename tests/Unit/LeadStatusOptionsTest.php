<?php

namespace Tests\Unit;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use PHPUnit\Framework\TestCase;

class LeadStatusOptionsTest extends TestCase
{
    public function test_lead_status_options_include_all_admin_states(): void
    {
        $this->assertSame([
            'new' => 'Нова',
            'sms' => 'SMS',
            'email' => 'Имейл',
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
        $this->assertSame('SMS', LeadResource::getStatusLabel('sms'));
        $this->assertSame('Имейл', LeadResource::getStatusLabel('email'));
    }

    public function test_lead_status_badge_colors_match_admin_mapping(): void
    {
        $this->assertSame([
            'warning' => 'new',
            'gray' => ['sms', 'email'],
            'info' => 'in_progress',
            'success' => ['processed', 'approved'],
            'danger' => 'rejected',
        ], LeadResource::getStatusBadgeColors());
    }

    public function test_marital_status_options_are_exposed_for_admin_ui(): void
    {
        $this->assertSame([
            Lead::MARITAL_STATUS_SINGLE => 'Неженен/Неомъжена',
            Lead::MARITAL_STATUS_MARRIED => 'Женен/Омъжена',
            Lead::MARITAL_STATUS_DIVORCED => 'Разведен/а',
            Lead::MARITAL_STATUS_WIDOWED => 'Вдовец/Вдовица',
            Lead::MARITAL_STATUS_COHABITING => 'На семейни начала',
        ], LeadResource::getMaritalStatusOptions());
    }

    public function test_marital_status_label_returns_human_readable_value(): void
    {
        $this->assertSame('Женен/Омъжена', LeadResource::getMaritalStatusLabel(Lead::MARITAL_STATUS_MARRIED));
        $this->assertSame('Няма', LeadResource::getMaritalStatusLabel(null));
    }
}
