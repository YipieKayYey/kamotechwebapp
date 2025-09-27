<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerifyExistingUsers extends Command
{
    protected $signature = 'users:verify-existing';

    protected $description = 'Mark all existing users as email verified';

    public function handle()
    {
        $this->info('Marking all existing users as email verified...');

        $count = User::whereNull('email_verified_at')
            ->update(['email_verified_at' => Carbon::now()]);

        $this->info("Successfully verified {$count} users!");

        // Show current status
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = User::whereNull('email_verified_at')->count();

        $this->info('Current status:');
        $this->info("Total users: {$totalUsers}");
        $this->info("Verified users: {$verifiedUsers}");
        $this->info("Unverified users: {$unverifiedUsers}");

        return Command::SUCCESS;
    }
}
