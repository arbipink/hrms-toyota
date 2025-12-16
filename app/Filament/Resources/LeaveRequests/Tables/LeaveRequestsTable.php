<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\LeaveRequest;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()->isAdmin()),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                    }),

                TextColumn::make('reason')
                    ->limit(30),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('approve')
                    ->action(function (LeaveRequest $record) {
                        $record->update(['status' => 'APPROVED']);
                        Notification::make()->title('Request Approved')->success()->send();
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (LeaveRequest $record) => auth()->user()->isAdmin() && $record->status === 'PENDING'),

                Action::make('reject')
                    ->action(function (LeaveRequest $record) {
                        $record->update(['status' => 'REJECTED']);
                        Notification::make()->title('Request Rejected')->danger()->send();
                    })
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (LeaveRequest $record) => auth()->user()->isAdmin() && $record->status === 'PENDING'),
            ]);
    }
}
