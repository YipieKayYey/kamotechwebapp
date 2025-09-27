<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register', [
            'recaptcha_site_key' => app()->environment('production') ? config('recaptcha.site_key') : null,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate reCAPTCHA first if provided
        if ($request->has('g-recaptcha-response')) {
            $recaptchaService = new RecaptchaService;
            if (! $recaptchaService->verify($request->input('g-recaptcha-response'), $request->ip())) {
                return back()->withErrors([
                    'recaptcha' => 'Please complete the reCAPTCHA verification.',
                ]);
            }
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_initial' => 'nullable|string|max:5',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => [
                'required',
                'string',
                'regex:/^(09\d{2}-\d{3}-\d{4}|09\d{9}|\+639\d{9}|639\d{9})$/',
            ],
            'date_of_birth' => 'required|date|before:18 years ago',
            'house_no_street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'nearest_landmark' => 'nullable|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex' => 'Please enter a valid Philippine mobile number (e.g., 0917-123-4567)',
        ]);

        // Normalize phone number to 09XXXXXXXXX format for storage
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (strlen($phone) === 10 && $phone[0] === '9') {
            $phone = '0'.$phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 2) === '63') {
            $phone = '0'.substr($phone, 2);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'middle_initial' => $request->middle_initial,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $phone,
            'date_of_birth' => $request->date_of_birth,
            'house_no_street' => $request->house_no_street ?? null,
            'barangay' => $request->barangay ?? null,
            'city_municipality' => $request->city_municipality ?? null,
            'province' => $request->province ?? null,
            'nearest_landmark' => $request->nearest_landmark ?? null,
            'password' => Hash::make($request->password),
        ]);

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 10 minutes
        $otpKey = 'otp_'.$user->id;
        Cache::put($otpKey, [
            'otp' => $otp,
            'email' => $user->email,
            'expires_at' => now()->addMinutes(10),
        ], 600); // 10 minutes

        // Send OTP email
        $user->notify(new \App\Notifications\OTPVerificationNotification($otp));

        Auth::login($user);

        return redirect()->route('auth.verify-otp')
            ->with('status', 'Registration successful! Please check your email for the 6-digit verification code.');
    }
}
