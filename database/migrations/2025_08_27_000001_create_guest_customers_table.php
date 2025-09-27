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
        Schema::create('guest_customers', function (Blueprint $table) {
            $table->id();

            // Name fields
            $table->string('first_name');
            $table->string('middle_initial', 5)->nullable();
            $table->string('last_name');

            // Contact fields
            $table->string('phone')->index(); // Primary lookup field
            $table->string('email')->nullable();

            // Address fields (same as users table)
            $table->string('house_no_street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('nearest_landmark')->nullable();

            // Tracking fields
            $table->text('notes')->nullable();
            $table->integer('total_bookings')->default(0);
            $table->timestamp('last_booking_date')->nullable();

            // Conversion tracking
            $table->foreignId('converted_to_user_id')->nullable()
                ->constrained('users')->onDelete('set null');

            // Admin tracking
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            // Indexes for performance
            $table->index(['first_name', 'last_name']);
            $table->index(['phone', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_customers');
    }
};
