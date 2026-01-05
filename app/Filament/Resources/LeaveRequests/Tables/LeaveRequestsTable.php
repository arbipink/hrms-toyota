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
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (! Auth::user()->isAdmin()) {
                    return $query->where('user_id', Auth::id());
                }
                return $query;
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn($record) => $record->admin_comment ? $record->admin_comment : null)
                    ->visible(fn() => Auth::user()->isAdmin()),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                    }),

                TextColumn::make('reason')
                    ->limit(35)
                    ->lineClamp(2)
                    ->tooltip(fn($record) => $record->reason ? $record->reason : null)
                    ->wrap(),

                TextColumn::make('admin_comment')
                    ->label('Admin Comment')
                    ->limit(35)
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn($record) => $record->admin_comment ? $record->admin_comment : null)
                    ->placeholder('No comment yet')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ])
                    ->default('PENDING'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->schema([
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
                    ->modalHeading('Approve Leave Request')
                    ->modalSubmitActionLabel('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(
                        fn(LeaveRequest $record) =>
                        Auth::user()->isAdmin() &&
                            $record->status === 'PENDING'
                    ),

                Action::make('reject')
                    ->schema([
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
                    ->modalHeading('Reject Leave Request')
                    ->modalSubmitActionLabel('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(
                        fn(LeaveRequest $record) =>
                        Auth::user()->isAdmin() &&
                            $record->status === 'PENDING'
                    ),

                EditAction::make()
                    ->visible(
                        fn(LeaveRequest $record) =>
                        Auth::user()->isAdmin() &&
                            $record->status !== 'PENDING'
                    ),

                Action::make('download_pdf')
                    ->label('Download Permit')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function (LeaveRequest $record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadView('pdf.leave-request', ['record' => $record])
                                ->stream();
                        }, 'leave-permit-' . $record->id . '.pdf');
                    })
                    ->visible(fn (LeaveRequest $record) => $record->status === 'APPROVED'),
            ]);
    }
}