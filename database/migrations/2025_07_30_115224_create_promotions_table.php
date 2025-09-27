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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            // Slider Content Fields
            $table->string('welcome_text')->nullable();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('background_image')->nullable();

            // Button Configuration
            $table->string('primary_button_text')->default('BOOK NOW');
            $table->string('primary_button_link')->default('/booking');
            $table->string('secondary_button_text')->nullable();
            $table->string('secondary_button_link')->nullable();

            // Promotion Details
            $table->enum('discount_type', ['percentage', 'fixed', 'free_service'])->nullable();
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->string('promo_code')->nullable();

            // Display Settings
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_slider')->default(true);

            // Validity Period
            $table->date('start_date');
            $table->date('end_date');

            // Targeting
            $table->json('applicable_services')->nullable();
            $table->json('applicable_aircon_types')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
