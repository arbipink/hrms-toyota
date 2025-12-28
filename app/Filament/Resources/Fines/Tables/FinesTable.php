<?php

namespace App\Filament\Resources\Fines\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user && !$user->isAdmin()) {
                    $query->where('user_id', $user->id);
                }
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Employee'),

                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger')
                    ->weight('bold'),

                TextColumn::make('reason')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->reason),

                TextColumn::make('attendance.date')
                    ->date()
                    ->label('Late/Absent Date')
                    ->placeholder('Manual Fine') 
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
