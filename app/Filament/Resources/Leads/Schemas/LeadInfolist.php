<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('full_name'),
                TextEntry::make('phone'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('city')
                    ->placeholder('-'),
                TextEntry::make('service_type'),
                TextEntry::make('amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('term_months')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('egn')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('monthly_income')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('employment_type')
                    ->placeholder('-'),
                TextEntry::make('monthly_debt')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->placeholder('-'),
                TextEntry::make('utm_source')
                    ->placeholder('-'),
                TextEntry::make('utm_campaign')
                    ->placeholder('-'),
                TextEntry::make('utm_medium')
                    ->placeholder('-'),
                TextEntry::make('gclid')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('assigned_user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('priority')
                    ->numeric(),
                TextEntry::make('consent_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('consent_ip')
                    ->placeholder('-'),
                TextEntry::make('consent_user_agent')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
