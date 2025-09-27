<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();

            // Customer Information
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('customer_name')->nullable(); // For guest/walk-in bookings

            // Service Details
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('aircon_type_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('number_of_units')->default(1); // Number of AC units to service
            $table->string('ac_brand')->nullable(); // AC brand (e.g., Samsung, LG, Carrier, "Unknown")

            // Assignment & Scheduling (dynamic)
            $table->foreignId('technician_id')->nullable()->constrained()->onDelete('set null');
            // Dynamic scheduling replaces timeslots: precise start/end datetimes
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');
            // Optional operational tracking
            $table->dateTime('actual_start_at')->nullable();
            $table->dateTime('actual_end_at')->nullable();
            // Deprecated: technician buffer removed; conflicts rely on planned windows only
            // Estimate for analytics/UX
            $table->integer('estimated_duration_minutes')->nullable();

            // Status & Payment
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'cancel_requested'])->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_status', ['pending', 'paid', 'unpaid'])->default('pending');

            // Cancellation Tracking
            $table->string('cancellation_reason')->nullable();
            $table->text('cancellation_details')->nullable();
            $table->timestamp('cancellation_requested_at')->nullable();
            $table->timestamp('cancellation_processed_at')->nullable();
            $table->foreignId('cancellation_processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();

            // Admin Tracking
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();

            // Address Information
            $table->boolean('use_custom_address')->default(false); // Toggle for using user's address vs custom
            $table->text('customer_address')->nullable(); // Can be null if using user's address
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('house_no_street')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->string('nearest_landmark')->nullable(); // For easier location finding

            // Additional Information
            $table->text('special_instructions')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
