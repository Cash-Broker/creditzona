<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Models\ContactMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Съобщение')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Име и фамилия'),
                        TextEntry::make('phone')
                            ->label('Телефон'),
                        TextEntry::make('email')
                            ->label('Имейл'),
                        TextEntry::make('assignedUser.name')
                            ->label('Оператор')
                            ->placeholder('Няма'),
                        TextEntry::make('archivedByUser.name')
                            ->label('Архивирано от')
                            ->placeholder('Няма'),
                        TextEntry::make('created_at')
                            ->label('Получено на')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('archived_at')
                            ->label('Архивирано на')
                            ->placeholder('Няма')
                            ->dateTime('d.m.Y H:i', 'Europe/Sofia'),
                        TextEntry::make('message')
                            ->label('Съобщение')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
                Section::make('Кореспонденция')
                    ->description('Изпратени отговори към подателя.')
                    ->visible(fn (ContactMessage $record): bool => $record->replies()->exists())
                    ->schema([
                        ViewEntry::make('replies_thread')
                            ->view('filament.resources.contact-messages.infolists.replies-thread')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
