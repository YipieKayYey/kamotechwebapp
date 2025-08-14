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
            
            // Assignment & Scheduling
            $table->foreignId('technician_id')->nullable()->constrained()->onDelete('set null');
            $table->date('scheduled_date'); // Start date
            $table->date('scheduled_end_date')->nullable(); // End date for multi-day jobs
            $table->foreignId('timeslot_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('estimated_duration_minutes')->nullable(); // Total job duration
            $table->integer('estimated_days')->default(1); // Number of days required
            
            // Status & Payment
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            
            // Address Information
            $table->text('customer_address');
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
