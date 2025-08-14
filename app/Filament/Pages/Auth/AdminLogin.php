<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AdminLogin extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->password()
            ->revealable()
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getCredentials(): array
    {
        return [
            'email' => $this->data['email'],
            'password' => $this->data['password'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        $credentials = $this->getCredentials();

        // First, attempt authentication
        if (! Auth::attempt($credentials, $this->data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        // Check if authenticated user is an admin
        $user = Auth::user();
        if ($user->role !== 'admin') {
            // Log out the user immediately
            Auth::logout();
            
            // Show specific error message based on role
            $roleMessage = match($user->role) {
                'customer' => 'ACCESS DENIED.',
                'technician' => 'ACCESS DENIED.',
                default => 'ACCESS DENIED.',
            };

            throw ValidationException::withMessages([
                'data.email' => $roleMessage,
            ]);
        }

        // If admin user, proceed with parent authentication
        return parent::authenticate();
    }
}