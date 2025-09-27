<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailSending extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:email';

    /**
     * The console command description.
     */
    protected $description = 'Test email sending configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing email configuration...');

        // Show current config
        $this->info('Mail driver: '.config('mail.default'));
        $this->info('Mail host: '.config('mail.mailers.smtp.host', 'Not configured'));
        $this->info('From address: '.config('mail.from.address'));

        try {
            // Test basic email sending
            Mail::raw('This is a test email from Laravel Kamotech', function ($message) {
                $message->to('jamesjologarcia@gmail.com')
                    ->subject('Laravel Email Test - Kamotech');
            });

            $this->info('✅ Test email sent successfully!');

            // Test verification email
            $user = User::first();
            if ($user) {
                $this->info('Testing verification email with user: '.$user->email);
                $user->sendEmailVerificationNotification();
                $this->info('✅ Verification email sent successfully!');
            } else {
                $this->warn('No users found to test verification email');
            }

        } catch (\Exception $e) {
            $this->error('❌ Email sending failed: '.$e->getMessage());

            // Show more details about the error
            $this->error('Error details: '.get_class($e));

            if (method_exists($e, 'getPrevious') && $e->getPrevious()) {
                $this->error('Previous error: '.$e->getPrevious()->getMessage());
            }
        }

        return 0;
    }
}
