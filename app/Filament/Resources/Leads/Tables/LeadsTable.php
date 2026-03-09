<?php

namespace App\Filament\Resources\Leads\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m H:i')->sortable(),
                Tables\Columns\TextColumn::make('full_name')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('service_type')->badge(),
                Tables\Columns\TextColumn::make('amount')->numeric(),
                Tables\Columns\SelectColumn::make('status')->options([
                    'new' => 'New',
                    'contacted' => 'Contacted',
                    'waiting_docs' => 'Waiting docs',
                    'submitted' => 'Submitted',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'done' => 'Done',
                    'lost' => 'Lost',
                ]),
                Tables\Columns\TextColumn::make('assignedUser.name')->label('Agent'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'new' => 'New',
                    'contacted' => 'Contacted',
                    'waiting_docs' => 'Waiting docs',
                    'submitted' => 'Submitted',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'done' => 'Done',
                    'lost' => 'Lost',
                ]),
                Tables\Filters\SelectFilter::make('service_type')->options([
                    'consumer' => 'Consumer',
                    'mortgage' => 'Mortgage',
                    'refinance' => 'Refinance',
                    'debt_buyout' => 'Debt buyout',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('call')
                    ->label('Call')
                    ->url(fn ($record) => 'tel:' . $record->phone)
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}