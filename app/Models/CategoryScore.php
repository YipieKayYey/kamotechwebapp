<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'category_id', 
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
        'review_id' => 'integer',
        'category_id' => 'integer',
    ];

    /**
     * Validation rules
     */
    public static function rules(): array
    {
        return [
            'review_id' => 'required|exists:ratings_reviews,id',
            'category_id' => 'required|exists:review_categories,id',
            'score' => 'required|integer|min:1|max:5',
        ];
    }

    /**
     * Relationships
     */
    public function review()
    {
        return $this->belongsTo(RatingReview::class, 'review_id');
    }

    public function category()
    {
        return $this->belongsTo(ReviewCategory::class, 'category_id');
    }

    /**
     * Model Events
     */
    protected static function boot()
    {
        parent::boot();

        // Recalculate overall rating when category score changes
        static::saved(function ($categoryScore) {
            if ($categoryScore->review) {
                $categoryScore->review->calculateOverallRating();
            }
        });

        static::deleted(function ($categoryScore) {
            if ($categoryScore->review) {
                $categoryScore->review->calculateOverallRating();
            }
        });
    }
}