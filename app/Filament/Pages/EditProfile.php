<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use BackedEnum;

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = "heroicon-o-user-circle";
    protected static ?string $navigationLabel = 'Profile';
    protected static ?string $title = 'Edit Profile';
    protected static ?string $slug = 'profile';
    protected string $view = 'filament.pages.edit-profile';
    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        
        if ($user) {
            $this->form->fill($user->attributesToArray());
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Profile Information')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->rule(fn () => Rule::unique('users', 'email')->ignore(Auth::id())),
                    ])->columns(2),

                Section::make('Update Password')
                    ->description('Leave empty if you do not want to change your password.')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->confirmed()
                            ->autocomplete('new-password'),
                        TextInput::make('password_confirmation')
                            ->password(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['password_confirmation']);

        $user = Auth::user();
        $user->update($data);

        Notification::make()
            ->success()
            ->title('Profile updated successfully')
            ->send();
    }
}