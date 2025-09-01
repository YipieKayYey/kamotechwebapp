<?php

namespace App\Filament\Technician\Pages;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationLabel = 'Profile Settings';
    
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.technician.pages.profile-settings';

    public ?array $profileData = [];
    public ?array $passwordData = [];

    public function mount(): void
    {
        $this->profileData = Auth::user()->toArray();
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema($this->getProfileFormSchema())
            ->statePath('profileData');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema($this->getPasswordFormSchema())
            ->statePath('passwordData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateProfile')
                ->label('Update Profile')
                ->action('updateProfile')
                ->color('primary'),

            Action::make('changePassword')
                ->label('Change Password')
                ->action('changePassword')
                ->color('warning'),
        ];
    }

    protected function getForms(): array 
    {
        return [
            'profileForm',
            'passwordForm',
        ];
    }

    public function updateProfile(): void
    {
        $data = $this->profileForm($this->makeForm()
            ->schema($this->getProfileFormSchema())
            ->statePath('profileData')
        )->getState();
        
        $user = Auth::user();
        $user->update($data);

        Notification::make()
            ->title('Profile updated successfully!')
            ->success()
            ->send();
    }

    public function changePassword(): void
    {
        $data = $this->passwordForm($this->makeForm()
            ->schema($this->getPasswordFormSchema())
            ->statePath('passwordData')
        )->getState();
        
        $user = Auth::user();
        $user->update([
            'password' => Hash::make($data['password'])
        ]);

        // Clear password form
        $this->passwordData = [];

        Notification::make()
            ->title('Password changed successfully!')
            ->success()
            ->send();
    }

    protected function getProfileFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Personal Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Phone Number')
                        ->tel()
                        ->maxLength(255),
                ])
                ->columns(2),

            Forms\Components\Section::make('Address Information')
                ->schema([
                    Forms\Components\TextInput::make('house_no_street')
                        ->label('House No. & Street')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('barangay')
                        ->label('Barangay')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('city_municipality')
                        ->label('City/Municipality')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('province')
                        ->label('Province')
                        ->maxLength(255),
                ])
                ->columns(2),
        ];
    }

    protected function getPasswordFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Change Password')
                ->schema([
                    Forms\Components\TextInput::make('current_password')
                        ->label('Current Password')
                        ->password()
                        ->required()
                        ->rules(['current_password']),

                    Forms\Components\TextInput::make('password')
                        ->label('New Password')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->confirmed(),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirm New Password')
                        ->password()
                        ->required(),
                ])
                ->columns(1),
        ];
    }
}
