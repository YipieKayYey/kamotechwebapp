<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'recaptcha_site_key' => app()->environment('production') ? config('recaptcha.site_key') : null,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Get the authenticated user
        $user = auth()->user();

        // Check if customer needs email verification
        if ($user->role === 'customer' && ! $user->hasVerifiedEmail()) {
            return redirect()->route('auth.verify-otp')
                ->with('status', 'Please verify your email address before accessing your dashboard. Check your email for the verification code.');
        }

        // Redirect based on user role
        switch ($user->role) {
            case 'admin':
                return redirect('/admin');
            case 'technician':
                return redirect('/technician');
            case 'customer':
            default:
                return redirect()->intended(route('customer-dashboard', absolute: false));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
