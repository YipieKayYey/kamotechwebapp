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
            $table->foreignId('guest_customer_id')->nullable()
                ->after('customer_id')
                ->constrained('guest_customers')
                ->onDelete('set null');

            // Add index for better query performance
            $table->index('guest_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['guest_customer_id']);
            $table->dropColumn('guest_customer_id');
        });
    }
};
