<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                
                TextInput::make('email')
                    ->email()
                    ->required(),

                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),

                Select::make('role')
                    ->options([
                        'ADMIN' => 'Admin',
                        'EMPLOYEE' => 'Employee',
                    ])
                    ->required()
                    ->default('EMPLOYEE'),

                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
