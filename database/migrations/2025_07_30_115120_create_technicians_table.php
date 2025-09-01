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
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('employee_id')->unique();
            $table->date('hire_date');
            $table->decimal('commission_rate', 5, 2)->default(15.00); // percentage
            $table->boolean('is_available')->default(true);
            $table->decimal('rating_average', 3, 2)->default(5.00);
            $table->integer('total_jobs')->default(0);
            $table->integer('current_jobs')->default(0);
            $table->integer('max_daily_jobs')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};
