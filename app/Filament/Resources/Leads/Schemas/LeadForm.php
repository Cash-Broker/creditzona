<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основни данни')
                    ->columns(2)
                    ->schema([
                        Select::make('credit_type')
                            ->label('Тип кредит')
                            ->options(LeadResource::getCreditTypeOptions())
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state !== 'mortgage') {
                                    $set('property_type', null);
                                    $set('property_location', null);
                                }
                            }),
                        Select::make('status')
                            ->label('Статус')
                            ->options(LeadResource::getStatusOptions())
                            ->required()
                            ->default('new')
                            ->native(false),
                        TextInput::make('first_name')
                            ->label('Име')
                            ->required()
                            ->maxLength(60),
                        TextInput::make('last_name')
                            ->label('Фамилия')
                            ->required()
                            ->maxLength(60),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label('Имейл')
                            ->email()
                            ->required()
                            ->maxLength(120),
                        TextInput::make('city')
                            ->label('Град')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('amount')
                            ->label('Сума')
                            ->required()
                            ->numeric()
                            ->minValue(5000)
                            ->maxValue(50000)
                            ->suffix('лв.'),
                    ]),
                Section::make('Данни за имота')
                    ->columns(2)
                    ->schema([
                        Select::make('property_type')
                            ->label('Тип имот')
                            ->options(LeadResource::getPropertyTypeOptions())
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('credit_type') === 'mortgage')
                            ->requiredIf('credit_type', 'mortgage'),
                        TextInput::make('property_location')
                            ->label('Местоположение на имота')
                            ->maxLength(120)
                            ->visible(fn (Get $get): bool => $get('credit_type') === 'mortgage')
                            ->requiredIf('credit_type', 'mortgage'),
                    ]),
            ]);
    }
}
