<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Determine redirect route based on user role
        $redirectRoute = match ($user->role) {
            'admin' => '/admin',
            'technician' => route('technician.dashboard', absolute: false),
            'customer' => route('customer-dashboard', absolute: false),
            default => route('customer-dashboard', absolute: false),
        };

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($redirectRoute)
                ->with('status', 'Your email is already verified. Welcome to Kamotech!');
        }

        $request->fulfill();

        return redirect()->intended($redirectRoute)
            ->with('status', 'Email verified successfully! Welcome to Kamotech.');
    }
}
