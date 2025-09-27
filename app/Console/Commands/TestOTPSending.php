<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\OTPVerificationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestOTPSending extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:otp';

    /**
     * The console command description.
     */
    protected $description = 'Test OTP email sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing OTP email system...');

        try {
            // Find any user for testing
            $user = User::first();

            if (! $user) {
                $this->error('No users found in database. Please run the seeders first.');

                return 1;
            }

            $this->info('Testing with user: '.$user->email);

            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $this->info('Generated OTP: '.$otp);

            // Store OTP in cache
            $otpKey = 'otp_'.$user->id;
            Cache::put($otpKey, [
                'otp' => $otp,
                'email' => $user->email,
                'expires_at' => now()->addMinutes(10),
            ], 600);

            // Send OTP email
            $user->notify(new OTPVerificationNotification($otp));

            $this->info('✅ OTP email sent successfully to: '.$user->email);
            $this->info('📧 Please check your email inbox for the 6-digit verification code.');
            $this->info('⏰ Code expires in 10 minutes.');

        } catch (\Exception $e) {
            $this->error('❌ Failed to send OTP: '.$e->getMessage());
            $this->error('Error details: '.get_class($e));

            if (method_exists($e, 'getPrevious') && $e->getPrevious()) {
                $this->error('Previous error: '.$e->getPrevious()->getMessage());
            }

            return 1;
        }

        return 0;
    }
}
