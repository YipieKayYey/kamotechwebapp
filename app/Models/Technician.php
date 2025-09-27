<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Technician extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'hire_date',
        'commission_rate',
        'is_available',
        'rating_average',
        'total_jobs',
        'current_jobs',
        'max_daily_jobs',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'commission_rate' => 'decimal:2',
        'is_available' => 'boolean',
        'rating_average' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Availability removed - using simple is_available toggle instead

    public function reviews(): HasMany
    {
        return $this->hasMany(RatingReview::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }



    public function scopeNotOverloaded($query)
    {
        return $query->whereColumn('current_jobs', '<', 'max_daily_jobs');
    }

    /**
     * Accessors & Mutators
     */
    public function getFullNameAttribute()
    {
        return $this->user->name;
    }

    /**
     * Service-Specific Rating Methods (For Greedy Algorithm)
     */
    public function getServiceSpecificRating($serviceId): float
    {
        $avgRating = $this->reviews()
            ->where('service_id', $serviceId)
            ->where('is_approved', true)
            ->avg('overall_rating');
            
        // Return service-specific rating or fall back to overall rating or default
        return $avgRating ?? $this->rating_average ?? 4.0;
    }

    public function getServiceSpecificReviewCount($serviceId): int
    {
        return $this->reviews()
            ->where('service_id', $serviceId)
            ->where('is_approved', true)
            ->count();
    }

    public function getServiceSpecificCompletedJobs($serviceId): int
    {
        return $this->bookings()
            ->where('service_id', $serviceId)
            ->where('status', 'completed')
            ->count();
    }

    public function getServiceRankingScore($serviceId): float
    {
        // Service Rating Score (70% weight) - Higher weight since no proximity
        $serviceRating = $this->getServiceSpecificRating($serviceId);
        $serviceScore = ($serviceRating - 1) / 4; // Normalize 1-5 to 0-1
        
        // Availability Score (30% weight)
        $availabilityScore = max(0, (($this->max_daily_jobs - $this->current_jobs) / $this->max_daily_jobs));
        
        // UPDATED GREEDY ALGORITHM FORMULA (No Proximity)
        return ($serviceScore * 0.7) + ($availabilityScore * 0.3);
    }


}
