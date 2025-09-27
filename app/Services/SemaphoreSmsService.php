<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SemaphoreSmsService
{
    protected string $apiKey;

    protected string $apiUrl;

    protected ?string $senderName;

    public function __construct()
    {
        $this->apiKey = config('services.semaphore.api_key');
        $this->apiUrl = config('services.semaphore.api_url', 'https://api.semaphore.co/api/v4/messages');
        // Don't use sender name to avoid validation errors - let Semaphore use default
        $this->senderName = null;
    }

    /**
     * Send booking confirmation SMS
     */
    public function sendBookingConfirmation(Booking $booking): bool
    {
        $phoneNumber = $this->getCustomerPhone($booking);

        if (! $phoneNumber) {
            Log::warning('SMS not sent: No phone number found', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
            ]);

            return false;
        }

        $message = $this->buildConfirmationMessage($booking);

        return $this->sendSms($phoneNumber, $message, $booking);
    }

    /**
     * Send new booking created SMS
     */
    public function sendNewBookingCreated(Booking $booking): bool
    {
        $phoneNumber = $this->getCustomerPhone($booking);

        if (! $phoneNumber) {
            Log::warning('SMS not sent: No phone number found', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
            ]);

            return false;
        }

        $message = $this->buildNewBookingMessage($booking);

        $result = $this->sendSms($phoneNumber, $message, $booking);

        // Update the last saved log to be new_booking type
        if ($result) {
            SmsLog::where('booking_id', $booking->id)
                ->where('message_type', 'confirmation')
                ->latest()
                ->first()
                ?->update(['message_type' => 'new_booking']);
        }

        return $result;
    }

    /**
     * Send booking cancellation SMS
     */
    public function sendBookingCancellation(Booking $booking): bool
    {
        $phoneNumber = $this->getCustomerPhone($booking);

        if (! $phoneNumber) {
            Log::warning('Cancellation SMS not sent: No phone number found', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
            ]);

            return false;
        }

        $message = $this->buildCancellationMessage($booking);

        return $this->sendSms($phoneNumber, $message, $booking);
    }

    /**
     * Send test SMS to custom phone number
     */
    public function sendTestSms(string $phoneNumber, Booking $booking): bool
    {
        // Format the test phone number properly
        $formattedPhone = $this->formatPhoneNumber($phoneNumber);

        if (! $formattedPhone) {
            Log::error('Invalid test phone number format', [
                'original_phone' => $phoneNumber,
                'booking_id' => $booking->id,
            ]);

            return false;
        }

        $message = $this->buildTestMessage($booking);

        Log::info('Sending test SMS', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'test_phone' => $phoneNumber,
            'formatted_phone' => $formattedPhone,
            'message_length' => strlen($message),
        ]);

        // Send SMS and save with 'test' message type
        $result = $this->sendSms($formattedPhone, $message, $booking);

        // Update the last saved log to be test type
        if ($result && $booking) {
            SmsLog::where('booking_id', $booking->id)
                ->whereNull('message_type')
                ->orWhere('message_type', 'confirmation')
                ->latest()
                ->first()
                ?->update(['message_type' => 'test']);
        }

        return $result;
    }

    /**
     * Get customer phone number with priority logic
     */
    protected function getCustomerPhone(Booking $booking): ?string
    {
        // Priority 1: Booking's customer_mobile
        if ($booking->customer_mobile) {
            return $this->formatPhoneNumber($booking->customer_mobile);
        }

        // Priority 2: Registered user's phone
        if ($booking->customer && $booking->customer->phone) {
            return $this->formatPhoneNumber($booking->customer->phone);
        }

        return null;
    }

    /**
     * Get customer name with priority logic
     */
    protected function getCustomerName(Booking $booking): string
    {
        // Priority 1: Registered user
        if ($booking->customer) {
            if (! empty($booking->customer->first_name)) {
                return trim($booking->customer->first_name);
            }
            if (! empty($booking->customer->name)) {
                $firstName = explode(' ', trim($booking->customer->name))[0];
                if (! empty($firstName)) {
                    return $firstName;
                }
            }
        }

        // Priority 2: Guest customer
        if ($booking->guestCustomer && ! empty($booking->guestCustomer->first_name)) {
            return trim($booking->guestCustomer->first_name);
        }

        // Priority 3: Customer name from booking
        if (! empty($booking->customer_name)) {
            $firstName = explode(' ', trim($booking->customer_name))[0];
            if (! empty($firstName)) {
                return $firstName;
            }
        }

        return 'Customer';
    }

    /**
     * Build confirmation message (optimized for 1 SMS credit)
     */
    protected function buildConfirmationMessage(Booking $booking): string
    {
        $customerName = $this->getCustomerName($booking);
        $serviceName = $this->truncateService($booking->service->name ?? 'Aircon Service');
        $scheduleDate = $booking->scheduled_start_at->format('M j, g:i A'); // Shorter format
        $technicianName = $this->truncateName($booking->technician->user->name ?? 'TBA');
        $technicianPhone = $this->formatPhoneNumber($booking->technician->user->phone ?? '') ?: 'TBA';

        $message = "KAMOTECH: Hi {$customerName}! Booking CONFIRMED.\n".
                   "Service: {$serviceName}\n".
                   "Date: {$scheduleDate}\n".
                   "Tech: {$technicianName} ({$technicianPhone})\n".
                   'Support: 0907-445-2484';

        // Ensure message stays under 160 characters for 1 credit
        return $this->ensureSingleSms($message);
    }

    /**
     * Build new booking message (optimized for 1 SMS credit)
     */
    protected function buildNewBookingMessage(Booking $booking): string
    {
        $customerName = $this->getCustomerName($booking);
        $serviceName = $this->truncateService($booking->service->name ?? 'Aircon Service');
        $scheduleDate = $booking->scheduled_start_at->format('M j, g:i A'); // Shorter format
        $technicianName = $this->truncateName($booking->technician->user->name ?? 'TBA');
        $technicianPhone = $this->formatPhoneNumber($booking->technician->user->phone ?? '') ?: 'TBA';

        $message = "KAMOTECH: Hi {$customerName}! Booking CONFIRMED.\n".
                   "Service: {$serviceName}\n".
                   "Date: {$scheduleDate}\n".
                   "Tech: {$technicianName} ({$technicianPhone})\n".
                   'Support: 0907-445-2484';

        // Ensure message stays under 160 characters for 1 credit
        return $this->ensureSingleSms($message);
    }

    /**
     * Build cancellation message (optimized for 1 SMS credit)
     */
    protected function buildCancellationMessage(Booking $booking): string
    {
        $customerName = $this->getCustomerName($booking);
        $serviceName = $this->truncateService($booking->service->name ?? 'Aircon Service');
        $scheduleDate = $booking->scheduled_start_at->format('M j, g:i A');

        $message = "KAMOTECH: Hi {$customerName}!\n".
                   "Booking CANCELLED.\n".
                   "Service: {$serviceName}\n".
                   "Date: {$scheduleDate}\n".
                   "Support: 0907-445-2484\n".
                   "You can book again anytime!";

        return $this->ensureSingleSms($message);
    }

    /**
     * Build test message
     */
    protected function buildTestMessage(Booking $booking): string
    {
        $serviceName = $booking->service->name ?? 'Air Conditioning Service';
        $scheduleDate = $booking->scheduled_start_at->format('M d, Y g:i A');
        $technicianName = $booking->technician->user->name ?? 'TBA';
        $technicianPhone = $this->formatPhoneNumber($booking->technician->user->phone ?? '') ?: 'TBA';

        return "🧪 KAMOTECH TEST SMS: This is a test message using booking data.\n\n".
               "Service: {$serviceName}\n".
               "Date: {$scheduleDate}\n".
               "Technician: {$technicianName}\n".
               "Contact: {$technicianPhone}\n\n".
               "Support: 0907 445 2484\n\n".
               '✅ SMS system working correctly!';
    }

    /**
     * Format phone number to Philippine format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to 09XXXXXXXXX format
        if (str_starts_with($phone, '639')) {
            $phone = '0'.substr($phone, 2);
        } elseif (str_starts_with($phone, '+639')) {
            $phone = '0'.substr($phone, 3);
        }

        // Validate Philippine mobile format
        if (preg_match('/^09\d{9}$/', $phone)) {
            return $phone;
        }

        return '';
    }

    /**
     * Send SMS via Semaphore API
     */
    protected function sendSms(string $phoneNumber, string $message, Booking $booking): bool
    {
        // Validate inputs before sending
        if (empty(trim($message))) {
            Log::error('SMS sending failed: Empty message', [
                'booking_id' => $booking->id ?? 'unknown',
                'booking_number' => $booking->booking_number ?? 'unknown',
                'phone' => $phoneNumber,
            ]);

            return false;
        }

        if (empty(trim($phoneNumber))) {
            Log::error('SMS sending failed: Empty phone number', [
                'booking_id' => $booking->id ?? 'unknown',
                'booking_number' => $booking->booking_number ?? 'unknown',
                'message_length' => strlen($message),
            ]);

            return false;
        }

        if (empty($this->apiKey)) {
            Log::error('SMS sending failed: Missing API key');

            return false;
        }

        try {
            // Log what we're about to send for debugging
            Log::info('Attempting to send SMS', [
                'booking_id' => $booking->id ?? 'unknown',
                'booking_number' => $booking->booking_number ?? 'unknown',
                'phone' => $phoneNumber,
                'message_length' => strlen($message),
                'sender_name' => $this->senderName,
                'api_url' => $this->apiUrl,
                'message_preview' => substr($message, 0, 50).(strlen($message) > 50 ? '...' : ''),
            ]);

            $payload = [
                'apikey' => $this->apiKey,
                'number' => $phoneNumber,
                'message' => $message,
            ];

            // Only include sender name if it's set and not empty
            if (! empty($this->senderName)) {
                $payload['sendername'] = $this->senderName;
            }

            $response = Http::timeout(30)->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                // Save to database based on Semaphore API response structure
                $this->saveSmsLog($booking, $phoneNumber, $message, $responseData, 'confirmation');

                Log::info('SMS sent successfully', [
                    'booking_id' => $booking->id ?? 'unknown',
                    'booking_number' => $booking->booking_number ?? 'unknown',
                    'phone' => $phoneNumber,
                    'message_length' => strlen($message),
                    'response' => $responseData,
                ]);

                return true;
            } else {
                Log::error('SMS sending failed', [
                    'booking_id' => $booking->id ?? 'unknown',
                    'booking_number' => $booking->booking_number ?? 'unknown',
                    'phone' => $phoneNumber,
                    'message_length' => strlen($message),
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                    'payload_sent' => $payload,
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('SMS sending exception', [
                'booking_id' => $booking->id ?? 'unknown',
                'booking_number' => $booking->booking_number ?? 'unknown',
                'phone' => $phoneNumber,
                'message_length' => strlen($message),
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            return false;
        }
    }

    /**
     * Truncate service name to save characters
     */
    protected function truncateService(string $serviceName): string
    {
        $shortcuts = [
            'Air Conditioning' => 'Aircon',
            'Installation' => 'Install',
            'Maintenance' => 'Service',
            'Repair' => 'Fix',
            'Cleaning' => 'Clean',
        ];

        foreach ($shortcuts as $long => $short) {
            $serviceName = str_ireplace($long, $short, $serviceName);
        }

        return strlen($serviceName) > 20 ? substr($serviceName, 0, 17).'...' : $serviceName;
    }

    /**
     * Truncate technician name to first name only
     */
    protected function truncateName(string $name): string
    {
        if ($name === 'TBA') {
            return $name;
        }

        $firstName = explode(' ', trim($name))[0];

        return strlen($firstName) > 12 ? substr($firstName, 0, 12) : $firstName;
    }

    /**
     * Ensure message fits in single SMS (160 characters) to use only 1 credit
     */
    protected function ensureSingleSms(string $message): string
    {
        // Validate input
        if (empty(trim($message))) {
            return 'KAMOTECH: Booking notification (details unavailable)';
        }

        // Remove extra whitespace and normalize line breaks
        $message = preg_replace('/\s+/', ' ', $message);
        $message = str_replace(' \n ', "\n", $message);
        $message = trim($message);

        if (strlen($message) <= 160) {
            return $message;
        }

        // If too long, create minimal version
        $lines = explode("\n", $message);
        $header = array_shift($lines); // Keep "KAMOTECH: Hi [name]! ..."

        // Ensure header exists
        if (empty($header)) {
            $header = 'KAMOTECH: Booking update';
        }

        // Calculate remaining space
        $remainingSpace = 160 - strlen($header) - 1; // -1 for newline

        if ($remainingSpace <= 0) {
            // Header too long, truncate it
            return substr($header, 0, 157).'...';
        }

        $compactInfo = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $compactInfo[] = $line;
        }

        if (empty($compactInfo)) {
            return $header;
        }

        $info = implode(' | ', $compactInfo);

        // If still too long, truncate info section
        if (strlen($info) > $remainingSpace) {
            $info = substr($info, 0, $remainingSpace - 3).'...';
        }

        $finalMessage = $header."\n".$info;

        // Final safety check
        if (empty(trim($finalMessage))) {
            return 'KAMOTECH: Booking notification';
        }

        return $finalMessage;
    }

    /**
     * Add method to check message length for debugging
     */
    public function getMessageLength(Booking $booking, string $type = 'confirmation'): array
    {
        $message = match ($type) {
            'confirmation' => $this->buildConfirmationMessage($booking),
            'new_booking' => $this->buildNewBookingMessage($booking),
            'cancellation' => $this->buildCancellationMessage($booking),
            'test' => $this->buildTestMessage($booking),
            default => $this->buildConfirmationMessage($booking)
        };

        return [
            'message' => $message,
            'length' => strlen($message),
            'credits_needed' => ceil(strlen($message) / 160),
            'fits_single_sms' => strlen($message) <= 160,
        ];
    }

    /**
     * Save SMS log to database based on Semaphore API response
     */
    protected function saveSmsLog(
        ?Booking $booking,
        string $phoneNumber,
        string $message,
        array $responseData,
        string $messageType = 'confirmation'
    ): void {
        try {
            // Handle both single message and array responses
            $smsData = is_array($responseData) && isset($responseData[0])
                ? $responseData[0]
                : $responseData;

            SmsLog::create([
                'message_id' => $smsData['message_id'] ?? null,
                'semaphore_user_id' => $smsData['user_id'] ?? null,
                'semaphore_user' => $smsData['user'] ?? null,
                'account_id' => $smsData['account_id'] ?? null,
                'account' => $smsData['account'] ?? null,
                'recipient' => $smsData['recipient'] ?? $phoneNumber,
                'message' => $smsData['message'] ?? $message,
                'sender_name' => $smsData['sender_name'] ?? $this->senderName ?? 'Semaphore',
                'network' => $smsData['network'] ?? null,
                'status' => $smsData['status'] ?? 'unknown',
                'type' => $smsData['type'] ?? 'single',
                'source' => $smsData['source'] ?? 'api',
                'booking_id' => $booking?->id,
                'message_type' => $messageType,
                'credits_used' => $this->calculateCreditsUsed($message),
                'raw_response' => $responseData,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save SMS log', [
                'error' => $e->getMessage(),
                'booking_id' => $booking?->id,
                'phone' => $phoneNumber,
            ]);
        }
    }

    /**
     * Calculate credits used based on message length
     */
    protected function calculateCreditsUsed(string $message): int
    {
        // Standard SMS: 1 credit per 160 characters
        return (int) ceil(strlen($message) / 160);
    }
}
