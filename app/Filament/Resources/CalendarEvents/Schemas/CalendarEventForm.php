<?php

namespace App\Filament\Resources\CalendarEvents\Schemas;

use App\Models\CalendarEvent;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class CalendarEventForm
{
    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public static function schema(bool $canManageUsers, array $userOptions): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Заглавие')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Toggle::make('all_day')
                        ->label('Целодневно събитие')
                        ->inline(false)
                        ->live(),
                    Select::make('event_type')
                        ->label('Тип')
                        ->options(CalendarEvent::getEventTypeOptions())
                        ->required()
                        ->native(false),
                    Select::make('status')
                        ->label('Статус')
                        ->options(CalendarEvent::getStatusOptions())
                        ->required()
                        ->native(false),
                    DateTimePicker::make('starts_at')
                        ->label('Начало')
                        ->required()
                        ->seconds(false)
                        ->native(false),
                    DateTimePicker::make('ends_at')
                        ->label('Край')
                        ->required()
                        ->seconds(false)
                        ->native(false),
                    $canManageUsers
                        ? Select::make('user_id')
                            ->label('Потребител')
                            ->options($userOptions)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                        : Hidden::make('user_id'),
                    Select::make('reminder_minutes_before')
                        ->label('Напомняне')
                        ->options(CalendarEvent::getReminderOptions())
                        ->native(false),
                    TextInput::make('location')
                        ->label('Локация')
                        ->maxLength(255),
                    ColorPicker::make('color')
                        ->label('Цвят'),
                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
