<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestSemaphoreApi extends Command
{
    protected $signature = 'test:semaphore-api {phone} {message?}';

    protected $description = 'Test direct Semaphore API call';

    public function handle(): int
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message') ?: 'Test message from KAMOTECH Laravel app';

        $apiKey = config('services.semaphore.api_key');
        $apiUrl = config('services.semaphore.api_url', 'https://api.semaphore.co/api/v4/messages');

        $this->info('🧪 Direct Semaphore API Test');
        $this->line("📱 Phone: {$phone}");
        $this->line("💬 Message: {$message}");
        $this->line('📊 Length: '.strlen($message).' characters');
        $this->line('🔑 API Key: '.substr($apiKey, 0, 8).'...');
        $this->line("🌐 API URL: {$apiUrl}");

        $payload = [
            'apikey' => $apiKey,
            'number' => $phone,
            'message' => $message,
        ];

        $this->info("\n📤 Sending to Semaphore...");
        $this->line('Payload: '.json_encode($payload, JSON_PRETTY_PRINT));

        try {
            $response = Http::timeout(30)->post($apiUrl, $payload);

            $this->info("\n📥 Response received:");
            $this->line('Status Code: '.$response->status());
            $this->line('Success: '.($response->successful() ? '✅ YES' : '❌ NO'));

            if ($response->successful()) {
                $data = $response->json();
                $this->line('Response Data: '.json_encode($data, JSON_PRETTY_PRINT));
                $this->info('✅ SMS should be sent successfully!');
            } else {
                $this->error('❌ API Error:');
                $this->error('Status: '.$response->status());
                $this->error('Body: '.$response->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
