<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\OTPVerificationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class OTPVerificationController extends Controller
{
    /**
     * Show the OTP verification form.
     */
    public function show(): Response|RedirectResponse
    {
        $user = Auth::user();

        // If user is already verified, redirect to dashboard
        if ($user->hasVerifiedEmail()) {
            return $this->redirectToDashboard($user);
        }

        return Inertia::render('auth/verify-otp', [
            'email' => $user->email,
            'status' => session('status'),
        ]);
    }

    /**
     * Verify the OTP code.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ], [
            'otp.required' => 'Please enter your verification code.',
            'otp.size' => 'Verification code must be exactly 6 digits.',
        ]);

        $user = Auth::user();
        $otpKey = 'otp_'.$user->id;
        $storedOtpData = Cache::get($otpKey);

        if (! $storedOtpData) {
            return back()->withErrors([
                'otp' => 'Verification code has expired. Please request a new one.',
            ]);
        }

        if ($storedOtpData['otp'] !== $request->otp) {
            return back()->withErrors([
                'otp' => 'Invalid verification code. Please try again.',
            ]);
        }

        // Mark user as verified
        $user->markEmailAsVerified();

        // Clear the OTP from cache
        Cache::forget($otpKey);

        return $this->redirectToDashboard($user)
            ->with('status', 'Email verified successfully! Welcome to Kamotech.');
    }

    /**
     * Resend OTP verification code.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectToDashboard($user);
        }

        // Generate new 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 10 minutes
        $otpKey = 'otp_'.$user->id;
        Cache::put($otpKey, [
            'otp' => $otp,
            'email' => $user->email,
            'expires_at' => now()->addMinutes(10),
        ], 600); // 10 minutes

        // Send OTP email
        $user->notify(new OTPVerificationNotification($otp));

        return back()->with('status', 'New verification code sent to your email!');
    }

    /**
     * Redirect user to appropriate dashboard based on role.
     */
    private function redirectToDashboard(User $user): RedirectResponse
    {
        return match ($user->role) {
            'admin' => redirect('/admin'),
            'technician' => redirect('/technician'),
            'customer' => redirect()->route('customer-dashboard'),
            default => redirect()->route('customer-dashboard'),
        };
    }
}
