<?php

namespace App\Filament\Resources\Fines\Schemas;

use Filament\Forms\Components\Select;
use Filament\Support\RawJs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class FineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Employee'),

                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('IDR')
                    ->default(50000)
                    ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                    ->stripCharacters('.'),

                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull()
                    ->placeholder('e.g., Lost ID Card, Late Arrival, etc.'),
            ]);
    }
}
