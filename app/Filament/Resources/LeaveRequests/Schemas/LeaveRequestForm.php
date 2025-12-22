<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->visible(fn () => auth()->user()->isAdmin())
                    ->default(fn () => auth()->id()),

                Select::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ])
                    ->visible(fn () => auth()->user()->isAdmin())
                    ->default('PENDING')
                    ->required(),

                DatePicker::make('start_date')
                    ->required()
                    ->native(false),

                DatePicker::make('end_date')
                    ->required()
                    ->native(false)
                    ->afterOrEqual('start_date'),

                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('admin_comment')
                    ->label('Admin Note')
                    ->columnSpanFull()
                    ->disabled(fn () => ! auth()->user()->isAdmin())
                    ->dehydrated(fn () => auth()->user()->isAdmin())
                    ->hidden(fn (?string $operation) => $operation === 'create' && ! auth()->user()->isAdmin()),
            ]);
    }
}