<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\ListLeads;
use Filament\Tables\Table;
use Tests\TestCase;

class LeadListPollingTest extends TestCase
{
    public function test_leads_list_page_polls_every_five_seconds(): void
    {
        $page = app(ListLeads::class);
        $table = LeadResource::table(Table::make($page));

        $this->assertSame('5s', $table->getPollingInterval());
    }
}
