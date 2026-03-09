<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\User;
use Filament\Forms\Components;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Components\Section::make('Basic')->schema([
                Components\TextInput::make('full_name')->required()->maxLength(120),
                Components\TextInput::make('phone')->required()->maxLength(30),
                Components\TextInput::make('email')->email()->maxLength(120),
                Components\TextInput::make('city')->maxLength(120),
            ])->columns(2),

            Components\Section::make('Service')->schema([
                Components\Select::make('service_type')->required()->options([
                    'consumer' => 'Consumer',
                    'mortgage' => 'Mortgage',
                    'refinance' => 'Refinance',
                    'debt_buyout' => 'Debt buyout',
                ]),
                Components\TextInput::make('amount')->numeric(),
                Components\TextInput::make('term_months')->numeric(),
            ])->columns(3),

            Components\Section::make('Workflow')->schema([
                Components\Select::make('status')->required()->options([
                    'new' => 'New',
                    'contacted' => 'Contacted',
                    'waiting_docs' => 'Waiting docs',
                    'submitted' => 'Submitted',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'done' => 'Done',
                    'lost' => 'Lost',
                ]),
                Components\Select::make('assigned_user_id')
                    ->label('Assigned agent')
                    ->options(User::role('agent')->pluck('name', 'id'))
                    ->searchable(),
                Components\Select::make('priority')->options([
                    1 => 'High',
                    2 => 'Normal',
                    3 => 'Low',
                ])->default(2),
            ])->columns(3),

            Components\Section::make('Sensitive')->schema([
                Components\TextInput::make('egn')->maxLength(10),
                Components\TextInput::make('monthly_income')->numeric(),
                Components\Select::make('employment_type')->options([
                    'contract' => 'Contract',
                    'self_employed' => 'Self employed',
                    'pensioner' => 'Pensioner',
                    'unemployed' => 'Unemployed',
                ]),
                Components\TextInput::make('monthly_debt')->numeric(),
            ])->columns(2),
        ]);
    }
}