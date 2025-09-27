<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            // Redirect verified users to their appropriate dashboard
            return match ($user->role) {
                'admin' => redirect('/admin'),
                'technician' => redirect('/technician'),
                'customer' => redirect()->route('customer-dashboard'),
                default => redirect()->route('customer-dashboard'),
            };
        }

        // Email verification page is no longer used; redirect to OTP verification flow
        return redirect()->route('auth.verify-otp');
    }
}
