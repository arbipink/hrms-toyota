<?php

namespace App\Filament\Resources\Attendances\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user && ! $user->isAdmin()) {
                    $query->where('user_id', $user->id);
                }
            })
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('clock_in_time')
                    ->time()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clock_out_time')
                    ->time()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('late_minutes')
                    ->numeric()
                    ->sortable()
                    ->color(fn($record) => $record->late_minutes > 0 && !$record->is_forgiven ? 'danger' : null),
                TextColumn::make('status')
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'ABSENT' => 'danger',
                        'LATE' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('ip_address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                IconColumn::make('is_forgiven')
                    ->label('Forgiven')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('forgive_reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable(),

                TextColumn::make('forgiver.name')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('period')
                    ->label('Period')
                    ->options([
                        'today' => 'Today',
                        '3_days' => 'Last 3 Days',
                        'week' => 'Last 7 Days',
                    ])
                    ->default('today')
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'];
                        if ($value === 'today') {
                            $query->whereDate('date', Carbon::today());
                        } elseif ($value === '3_days') {
                            $query->where('date', '>=', Carbon::now()->subDays(3));
                        } elseif ($value === 'week') {
                            $query->where('date', '>=', Carbon::now()->subWeek());
                        }
                    }),

                SelectFilter::make('status_filter')
                    ->label('Attendance Issues')
                    ->multiple()
                    ->options([
                        'LATE' => 'Late',
                        'ABSENT' => 'Absent',
                    ])
                    ->default(['LATE', 'ABSENT'])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['values'])) {
                            $query->whereIn('status', $data['values']);
                        }
                    }),
            ])
            ->recordActions([
                Action::make('forgive')
                    ->label('Forgive')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalHeading('Forgive Lateness')
                    ->modalSubmitActionLabel('Forgive')
                    ->visible(fn($record) => Auth::user()?->isAdmin() && !$record->is_forgiven)
                    ->schema([
                        Textarea::make('forgive_reason')
                            ->label('Reason for forgiveness (Optional)')
                            ->placeholder('Explain why this lateness is forgiven...')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'is_forgiven' => true,
                            'forgiven_by' => Auth::id(),
                            'forgive_reason' => $data['forgive_reason'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Attendance Forgiven')
                            ->success()
                            ->send();
                    }),

                Action::make('unforgive')
                    ->label('Unmark Forgiven')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->modalHeading('Unmark as Forgiven')
                    ->modalDescription('Are you sure you want to revert this? The record will be marked as late/absent again.')
                    ->modalSubmitActionLabel('Unmark')
                    ->visible(fn($record) => Auth::user()?->isAdmin() && $record->is_forgiven)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'is_forgiven' => false,
                            'forgiven_by' => null,
                            'forgive_reason' => null,
                        ]);

                        Notification::make()
                            ->title('Forgiveness Reverted')
                            ->warning()
                            ->send();
                    }),
            ]);
    }
}