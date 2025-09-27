<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();

            // Semaphore API Response Fields (based on API docs)
            $table->string('message_id')->nullable(); // Semaphore's unique message ID
            $table->string('semaphore_user_id')->nullable(); // Semaphore user ID
            $table->string('semaphore_user')->nullable(); // Semaphore user email
            $table->string('account_id')->nullable(); // Semaphore account ID
            $table->string('account')->nullable(); // Semaphore account name
            $table->string('recipient'); // Phone number
            $table->text('message'); // SMS content
            $table->string('sender_name')->nullable(); // Sender name used
            $table->string('network')->nullable(); // Globe, Smart, etc.
            $table->string('status'); // Queued, Pending, Sent, Failed, Refunded
            $table->string('type')->nullable(); // single, bulk, priority
            $table->string('source')->default('api'); // api, webtool, csv

            // Our Application Fields
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('message_type', ['confirmation', 'new_booking', 'test', 'otp'])->default('confirmation');
            $table->integer('credits_used')->default(1);
            $table->json('raw_response')->nullable(); // Store full API response
            $table->timestamp('sent_at')->nullable(); // When we sent the request

            $table->timestamps();

            // Indexes
            $table->index(['booking_id', 'message_type']);
            $table->index(['status', 'sent_at']);
            $table->index('recipient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
