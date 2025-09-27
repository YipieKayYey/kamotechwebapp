<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        // Simple approach: modify the redirect URL to include prompt parameter
        $driver = Socialite::driver('google');
        $url = $driver->redirect()->getTargetUrl();

        // Add prompt=select_account to force account selection
        $urlWithPrompt = $url.(strpos($url, '?') ? '&' : '?').'prompt=select_account';

        return redirect($urlWithPrompt);
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists with this Google ID
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                // Update user info and ensure email is verified
                $user->update([
                    'avatar_original' => $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(), // Ensure Google users are verified
                ]);
            } else {
                // Check if user exists with same email
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    // Link existing account with Google and verify email
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar_original' => $googleUser->getAvatar(),
                        'avatar' => $googleUser->getAvatar(),
                        'email_verified_at' => $user->email_verified_at ?? now(), // Verify email for Google users
                    ]);
                } else {
                    // Create new user
                    $nameParts = $this->parseFullName($googleUser->getName());

                    $user = User::create([
                        'first_name' => $nameParts['first_name'],
                        'middle_initial' => $nameParts['middle_initial'],
                        'last_name' => $nameParts['last_name'],
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'avatar_original' => $googleUser->getAvatar(),
                        'email_verified_at' => now(), // Google users are automatically verified
                        'password' => Hash::make(uniqid()), // Generate random password for OAuth users
                        'role' => 'customer', // Default role
                        'is_active' => true,
                    ]);
                }
            }

            Auth::login($user, true); // Remember the user

            // Redirect based on user role
            switch ($user->role) {
                case 'admin':
                    return redirect('/admin');
                case 'technician':
                    return redirect('/technician');
                case 'customer':
                default:
                    return redirect()->intended(route('customer-dashboard'));
            }

        } catch (Exception $e) {
            \Log::error('Google OAuth Error: '.$e->getMessage());

            return redirect()->route('login')->with('status', 'Unable to login with Google. Please try again or use email/password login.');
        }
    }

    /**
     * Parse full name into parts
     */
    private function parseFullName($fullName)
    {
        $nameParts = explode(' ', trim($fullName));
        $result = [
            'first_name' => '',
            'middle_initial' => null,
            'last_name' => '',
        ];

        if (count($nameParts) == 1) {
            $result['first_name'] = $nameParts[0];
            $result['last_name'] = $nameParts[0]; // Use same as last name if only one name
        } elseif (count($nameParts) == 2) {
            $result['first_name'] = $nameParts[0];
            $result['last_name'] = $nameParts[1];
        } else {
            $result['first_name'] = $nameParts[0];
            $result['last_name'] = end($nameParts);

            // Check if there's a middle initial
            if (count($nameParts) > 2) {
                $middlePart = $nameParts[1];
                if (strlen($middlePart) <= 2) {
                    $result['middle_initial'] = str_replace('.', '', $middlePart);
                } else {
                    // If middle part is longer than 2 chars, include it in the last name
                    $result['last_name'] = implode(' ', array_slice($nameParts, 1));
                }
            }
        }

        return $result;
    }
}
