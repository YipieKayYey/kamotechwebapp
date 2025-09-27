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
        Schema::table('bookings', function (Blueprint $table) {
            // Add promotion tracking
            $table->foreignId('promotion_id')->nullable()->after('total_amount')->constrained('promotions')->onDelete('set null');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('promotion_id');
            $table->decimal('original_amount', 10, 2)->nullable()->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['promotion_id']);
            $table->dropColumn(['promotion_id', 'discount_amount', 'original_amount']);
        });
    }
};
