<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\ContactMessageService;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ContactMessagesTable
{
    public static function configure(Table $table, bool $isAttachedResource = false, bool $isArchiveResource = false): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Име и фамилия')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Имейл')
                    ->searchable(),
                TextColumn::make('assignedUser.name')
                    ->label('Оператор')
                    ->placeholder('Няма')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('archivedByUser.name')
                    ->label('Архивирано от')
                    ->placeholder('Няма')
                    ->sortable()
                    ->toggleable()
                    ->visible($isArchiveResource),
                TextColumn::make('message')
                    ->label('Съобщение')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Получено на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('archived_at')
                    ->label('Архивирано на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->toggleable()
                    ->visible($isArchiveResource),
            ])
            ->filters([
                //
            ])
            ->recordActions(array_values(array_filter([
                $isAttachedResource && ! $isArchiveResource ? ContactMessageResource::makeCreateLeadAction() : null,
                (! $isAttachedResource && ! $isArchiveResource) ? ContactMessageResource::makeAssignAction() : null,
                ! $isArchiveResource ? ContactMessageResource::makeArchiveAction() : null,
                ViewAction::make(),
            ])))
            ->defaultSort('created_at', 'desc')
            ->toolbarActions(array_values(array_filter([
                ! $isArchiveResource ? BulkAction::make('archive_messages')
                    ->label('Архивирай избраните')
                    ->icon('heroicon-m-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $actor = auth()->user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        $service = app(ContactMessageService::class);

                        $records->each(fn (ContactMessage $message) => $service->archiveMessage($message, $actor));

                        Notification::make()
                            ->title('Избраните съобщения са архивирани.')
                            ->success()
                            ->send();
                    }) : null,
            ])));
    }
}
