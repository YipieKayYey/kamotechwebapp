<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\SemaphoreSmsService;
use Illuminate\Console\Command;

class TestSemaphoreSms extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:sms {--booking-id=} {--phone= : Mobile number to send test SMS (e.g., 09171234567)}';

    /**
     * The console command description.
     */
    protected $description = 'Test Semaphore SMS service with a booking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Semaphore SMS Service...');

        // Check configuration
        $apiKey = config('services.semaphore.api_key');
        if (! $apiKey) {
            $this->error('SEMAPHORE_API_KEY not configured in .env file');

            return 1;
        }

        $this->info('✓ API Key configured');

        // Get booking for test
        $bookingId = $this->option('booking-id');

        if ($bookingId) {
            $booking = Booking::with(['service', 'technician.user', 'customer', 'guestCustomer'])->find($bookingId);

            if (! $booking) {
                $this->error("Booking with ID {$bookingId} not found");

                return 1;
            }
        } else {
            // Get first booking with required relationships
            $booking = Booking::with(['service', 'technician.user', 'customer', 'guestCustomer'])
                ->whereHas('service')
                ->first();

            if (! $booking) {
                $this->error('No bookings found for testing');

                return 1;
            }
        }

        $this->info("Testing with Booking #{$booking->booking_number}");

        // Initialize SMS service
        $smsService = new SemaphoreSmsService;

        // Check for custom phone number
        $testPhone = $this->option('phone');

        // Display customer info
        $customerName = 'N/A';
        $customerPhone = 'N/A';
        $actualRecipient = 'N/A';

        if ($booking->customer) {
            $customerName = $booking->customer->name ?? 'N/A';
            $customerPhone = $booking->customer->phone ?? $booking->customer_mobile ?? 'N/A';
        } elseif ($booking->guestCustomer) {
            $customerName = $booking->guestCustomer->full_name ?? 'N/A';
            $customerPhone = $booking->customer_mobile ?? 'N/A';
        } else {
            $customerPhone = $booking->customer_mobile ?? 'N/A';
        }

        // Determine who will receive the SMS
        if ($testPhone) {
            $actualRecipient = $this->formatTestPhone($testPhone);
            if (! $actualRecipient) {
                $this->error('Invalid phone number format. Use Philippine format: 09XXXXXXXXX');

                return 1;
            }
            $this->warn("🧪 TEST MODE: SMS will be sent to YOUR number: {$actualRecipient}");
        } else {
            $actualRecipient = $customerPhone;
            $this->info("📱 SMS will be sent to booking customer: {$actualRecipient}");
        }

        // Show message length analysis
        $messageInfo = $smsService->getMessageLength($booking, 'confirmation');

        $this->table(['Field', 'Value'], [
            ['Customer Name', $customerName],
            ['Booking Phone', $customerPhone],
            ['📱 SMS Recipient', $actualRecipient],
            ['Service', $booking->service->name ?? 'N/A'],
            ['Scheduled', $booking->scheduled_start_at->format('M d, Y g:i A')],
            ['Technician', $booking->technician->user->name ?? 'N/A'],
            ['Tech Phone', $booking->technician->user->phone ?? 'N/A'],
        ]);

        // Show credit usage info
        $creditColor = $messageInfo['fits_single_sms'] ? 'info' : 'error';
        $this->newLine();
        $this->line("<{$creditColor}>💰 CREDIT USAGE ANALYSIS</{$creditColor}>");
        $this->table(['Metric', 'Value'], [
            ['Message Length', $messageInfo['length'].' characters'],
            ['Credits Needed', $messageInfo['credits_needed'].' credit(s)'],
            ['Single SMS?', $messageInfo['fits_single_sms'] ? '✅ Yes' : '❌ No (will be truncated)'],
        ]);

        $this->newLine();
        $this->line('<comment>📝 PREVIEW MESSAGE:</comment>');
        $this->line($messageInfo['message']);
        $this->newLine();

        if ($this->confirm('Send test SMS?')) {
            $this->info('Sending SMS...');

            if ($testPhone) {
                // Send to custom phone number
                $result = $smsService->sendTestSms($actualRecipient, $booking);
            } else {
                // Send to booking's customer phone
                $result = $smsService->sendBookingConfirmation($booking);
            }

            if ($result) {
                $this->info('✅ SMS sent successfully!');
                $this->info('📱 Check your phone: '.$actualRecipient);
                $this->info('📋 Check application logs for detailed response.');
            } else {
                $this->error('❌ SMS sending failed.');
                $this->error('Check the application logs for error details.');
            }
        }

        return 0;
    }

    /**
     * Format and validate test phone number
     */
    private function formatTestPhone(string $phone): ?string
    {
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to 09XXXXXXXXX format
        if (str_starts_with($phone, '639')) {
            $phone = '0'.substr($phone, 2);
        } elseif (str_starts_with($phone, '+639')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '9') && strlen($phone) === 10) {
            $phone = '0'.$phone;
        }

        // Validate Philippine mobile format
        if (preg_match('/^09\d{9}$/', $phone)) {
            return $phone;
        }

        return null;
    }
}
