<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\SemaphoreSmsService;
use Illuminate\Console\Command;

class DebugSms extends Command
{
    protected $signature = 'debug:sms {--booking-id=} {--phone=}';

    protected $description = 'Debug SMS message generation without sending';

    public function handle(): int
    {
        $this->info('🔍 SMS Debug Tool');

        // Check configuration
        $apiKey = config('services.semaphore.api_key');
        $senderName = config('services.semaphore.sender_name');
        $apiUrl = config('services.semaphore.api_url');

        $this->info('📋 Configuration Check:');
        $this->line('API Key: '.($apiKey ? '✅ Set ('.substr($apiKey, 0, 8).'...)' : '❌ Missing'));
        $this->line('Sender Name: '.($senderName ?: 'Not Set'));
        $this->line('API URL: '.($apiUrl ?: 'Using default'));

        // Get booking
        $bookingId = $this->option('booking-id');

        if ($bookingId) {
            $booking = Booking::with(['service', 'technician.user', 'customer', 'guestCustomer'])->find($bookingId);
        } else {
            $booking = Booking::with(['service', 'technician.user', 'customer', 'guestCustomer'])
                ->whereHas('service')
                ->first();
        }

        if (! $booking) {
            $this->error('❌ No booking found');

            return 1;
        }

        $this->info("\n🎯 Testing with Booking #{$booking->booking_number}");

        // Initialize SMS service
        $smsService = new SemaphoreSmsService;

        // Test message generation
        $this->info("\n📝 Message Generation Test:");

        try {
            // Test confirmation message
            $confirmInfo = $smsService->getMessageLength($booking, 'confirmation');
            $this->displayMessageInfo('CONFIRMATION MESSAGE', $confirmInfo);

            // Test new booking message
            $newBookingInfo = $smsService->getMessageLength($booking, 'new_booking');
            $this->displayMessageInfo('NEW BOOKING MESSAGE', $newBookingInfo);

            // Test customer phone resolution
            $this->info("\n📱 Phone Number Resolution:");
            $reflection = new \ReflectionClass($smsService);
            $method = $reflection->getMethod('getCustomerPhone');
            $method->setAccessible(true);
            $customerPhone = $method->invoke($smsService, $booking);

            $testPhone = $this->option('phone');
            if ($testPhone) {
                $this->line("Test Phone (provided): {$testPhone}");
                $formatMethod = $reflection->getMethod('formatPhoneNumber');
                $formatMethod->setAccessible(true);
                $formattedTest = $formatMethod->invoke($smsService, $testPhone);
                $this->line('Test Phone (formatted): '.($formattedTest ?: '❌ Invalid format'));
            }

            $this->line('Resolved Phone: '.($customerPhone ?: '❌ No phone found'));
            $this->line('Booking customer_mobile: '.($booking->customer_mobile ?: 'Not set'));
            $this->line('User phone: '.($booking->customer?->phone ?: 'Not set'));

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return 1;
        }

        $this->info("\n✅ Debug complete! Check the messages above for any issues.");

        return 0;
    }

    private function displayMessageInfo(string $title, array $info): void
    {
        $this->line("\n<comment>{$title}:</comment>");
        $this->line("Length: {$info['length']} characters");
        $this->line("Credits: {$info['credits_needed']}");
        $this->line('Single SMS: '.($info['fits_single_sms'] ? '✅ Yes' : '❌ No'));
        $this->line('Message:');
        $this->line('---');
        $this->line($info['message']);
        $this->line('---');
    }
}
