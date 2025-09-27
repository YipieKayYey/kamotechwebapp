<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestBrevoEmail extends Command
{
    protected $signature = 'email:test {email}';

    protected $description = 'Test Brevo email configuration';

    public function handle()
    {
        $email = $this->argument('email');

        try {
            Mail::raw('This is a test email from Kamotech to verify Brevo SMTP configuration is working correctly.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Kamotech - Test Email');
            });

            $this->info('Test email sent successfully to '.$email);
            $this->info('Please check your inbox (and spam folder) for the test email.');
        } catch (\Exception $e) {
            $this->error('Failed to send test email: '.$e->getMessage());
            $this->error('Please check your Brevo credentials in the .env file.');
        }
    }
}
