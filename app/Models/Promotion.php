<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Promotion extends Model
{
    protected $fillable = [
        'welcome_text',
        'title',
        'subtitle',
        'background_image',
        'primary_button_text',
        'primary_button_link',
        'secondary_button_text',
        'secondary_button_link',
        'discount_type',
        'discount_value',
        'promo_code',
        'display_order',
        'is_active',
        'show_on_slider',
        'start_date',
        'end_date',
        'applicable_services',
        'applicable_aircon_types',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_slider' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_value' => 'decimal:2',
        'applicable_services' => 'array',
        'applicable_aircon_types' => 'array',
    ];

    /**
     * Scope for active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for slider promotions
     */
    public function scopeForSlider($query)
    {
        return $query->active()
            ->where('show_on_slider', true)
            ->orderBy('display_order', 'asc');
    }

    /**
     * Get the background image URL
     */
    public function backgroundImageUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $attributes['background_image']) {
                    return '/images/slide/1.jpg';
                }

                $imagePath = $attributes['background_image'];

                // If it's already a full URL, return it
                if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                    return $imagePath;
                }

                // If it starts with /images/, it's a public file - return as is
                if (str_starts_with($imagePath, '/images/')) {
                    return $imagePath;
                }

                // Otherwise, it's a storage file
                return Storage::url($imagePath);
            }
        );
    }

    /**
     * Get formatted discount display
     */
    public function getFormattedDiscountAttribute()
    {
        if (! $this->discount_type || ! $this->discount_value) {
            return null;
        }

        return match ($this->discount_type) {
            'percentage' => $this->discount_value.'% OFF',
            'fixed' => '₱'.number_format($this->discount_value).' OFF',
            'free_service' => 'FREE SERVICE',
            default => null
        };
    }

    /**
     * Check if promotion is currently valid
     */
    public function isValid()
    {
        return $this->is_active
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    /**
     * Get applicable services
     */
    public function services()
    {
        if (empty($this->applicable_services)) {
            return Service::query();
        }

        return Service::whereIn('id', $this->applicable_services);
    }

    /**
     * Get applicable aircon types
     */
    public function airconTypes()
    {
        if (empty($this->applicable_aircon_types)) {
            return AirconType::query();
        }

        return AirconType::whereIn('id', $this->applicable_aircon_types);
    }
}
