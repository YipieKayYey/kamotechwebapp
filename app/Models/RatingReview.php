<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingReview extends Model
{
    protected $table = 'ratings_reviews';

    protected $fillable = [
        'booking_id',
        'customer_id',
        'technician_id',
        'service_id',
        'overall_rating',
        'review',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'overall_rating' => 'float',
    ];

    /**
     * Relationships
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function categoryScores()
    {
        return $this->hasMany(CategoryScore::class, 'review_id');
    }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('overall_rating', $rating);
    }

    /**
     * Calculate and update overall rating from category scores
     */
    public function calculateOverallRating()
    {
        $scores = $this->categoryScores;
        
        if ($scores->isEmpty()) {
            $this->overall_rating = null;
        } else {
            $average = $scores->avg('score');
            $this->overall_rating = round($average, 2);
        }
        
        $this->save();
        return $this->overall_rating;
    }

    /**
     * Get category scores as an associative array
     */
    public function getCategoryScoresArray()
    {
        return $this->categoryScores()
            ->with('category')
            ->get()
            ->pluck('score', 'category.name')
            ->toArray();
    }
}
