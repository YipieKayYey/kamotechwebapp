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
        Schema::create('category_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('ratings_reviews')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('review_categories')->onDelete('cascade');
            $table->tinyInteger('score')->unsigned(); // 1-5 rating
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['review_id', 'category_id']);
            $table->unique(['review_id', 'category_id']); // One score per category per review
            
            // Note: Score validation (1-5) will be handled at the application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_scores');
    }
};
