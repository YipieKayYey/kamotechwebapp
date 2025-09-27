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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Normalized name fields
            $table->string('first_name');
            $table->string('middle_initial', 5)->nullable();
            $table->string('last_name');
            $table->string('name')->virtualAs("CONCAT(first_name, IF(middle_initial IS NOT NULL, CONCAT(' ', middle_initial, '.'), ''), ' ', last_name)"); // Virtual column for full name

            // Account fields
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); // Made nullable for OAuth users
            $table->string('phone')->nullable(); // Made nullable for OAuth users
            $table->date('date_of_birth')->nullable(); // Made nullable for OAuth users

            // OAuth fields
            $table->string('google_id')->nullable()->unique();
            $table->string('google_token')->nullable();
            $table->string('google_refresh_token')->nullable();
            $table->string('avatar_original')->nullable(); // Store Google avatar URL

            // Normalized address fields
            $table->string('house_no_street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('nearest_landmark')->nullable();

            // Role and status
            $table->enum('role', ['customer', 'technician', 'admin'])->default('customer');
            $table->boolean('is_active')->default(true);
            $table->string('avatar')->nullable();

            // Indexes for better performance
            $table->index(['first_name', 'last_name']);
            $table->index(['province', 'city_municipality']);
            $table->index(['email', 'phone']);

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
