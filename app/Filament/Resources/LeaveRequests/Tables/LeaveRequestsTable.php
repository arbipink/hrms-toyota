<?php
namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Models\LeaveRequest;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (! auth()->user()->isAdmin()) {
                    return $query->where('user_id', auth()->id());
                }
                return $query;
            })
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
                
                TextColumn::make('admin_comment')
                    ->label('Admin Comment')
                    ->limit(40)
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn ($record) => $record->admin_comment ? $record->admin_comment : null)
                    ->placeholder('No comment yet'),
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
                
                // Approve action - works for PENDING and REJECTED requests
                Action::make('approve')
                    ->form([
                        Textarea::make('admin_comment')
                            ->label('Comment (Optional)')
                            ->placeholder('Add a comment about this approval...')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'APPROVED',
                            'admin_comment' => $data['admin_comment'] ?? $record->admin_comment,
                        ]);
                        
                        Notification::make()
                            ->title('Request Approved')
                            ->success()
                            ->send();
                    })
                    ->modalHeading(fn (LeaveRequest $record) => 
                        $record->status === 'PENDING' 
                            ? 'Approve Leave Request' 
                            : 'Change to Approved'
                    )
                    ->modalSubmitActionLabel('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (LeaveRequest $record) => 
                        auth()->user()->isAdmin() && 
                        in_array($record->status, ['PENDING', 'REJECTED'])
                    ),
                
                // Reject action - works for PENDING and APPROVED requests
                Action::make('reject')
                    ->form([
                        Textarea::make('admin_comment')
                            ->label('Comment (Optional)')
                            ->placeholder('Add a comment about this rejection...')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'REJECTED',
                            'admin_comment' => $data['admin_comment'] ?? $record->admin_comment,
                        ]);
                        
                        Notification::make()
                            ->title('Request Rejected')
                            ->danger()
                            ->send();
                    })
                    ->modalHeading(fn (LeaveRequest $record) => 
                        $record->status === 'PENDING' 
                            ? 'Reject Leave Request' 
                            : 'Change to Rejected'
                    )
                    ->modalSubmitActionLabel('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (LeaveRequest $record) => 
                        auth()->user()->isAdmin() && 
                        in_array($record->status, ['PENDING', 'APPROVED'])
                    ),
            ]);
    }
}