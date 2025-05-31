<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationLabel = 'Profile';
    
    protected static ?string $title = 'My Profile';
    
    protected static string $view = 'filament.pages.user-profile';
    
    protected static bool $shouldRegisterNavigation = false;

    public ?array $profileData = [];
    public ?array $passwordData = [];

    public function mount(): void
    {
        $user = auth()->user();
        
        $this->profileData = [
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    public function getProfileForm(): Form
    {
        return Form::make($this)
            ->schema([
                Section::make('Profile Information')
                    ->description('Update your account profile information.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique('users', 'email', auth()->user()),
                    ])
                    ->columns(2),
            ])
            ->statePath('profileData');
    }

    public function getPasswordForm(): Form
    {
        return Form::make($this)
            ->schema([
                Section::make('Update Password')
                    ->description('Ensure your account is using a long, random password to stay secure.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->required()
                            ->rules([
                                function (string $attribute, $value, \Closure $fail) {
                                    if (!Hash::check($value, auth()->user()->password)) {
                                        $fail('The current password is incorrect.');
                                    }
                                },
                            ]),
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->same('password_confirmation')
                            ->live(onBlur: true),
                        TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required()
                            ->dehydrated(false),
                    ]),
            ])
            ->statePath('passwordData');
    }

    public function updateProfile(): void
    {
        $data = $this->getProfileForm()->getState();
        
        auth()->user()->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    public function updatePassword(): void
    {
        $data = $this->getPasswordForm()->getState();
        
        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        // Clear the password form
        $this->passwordData = [];

        Notification::make()
            ->title('Password updated successfully')
            ->success()
            ->send();
    }
}