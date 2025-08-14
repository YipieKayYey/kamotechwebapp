<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePricing extends Model
{
    protected $table = 'service_pricing';
    
    protected $fillable = [
        'service_id',
        'aircon_type_id',
        'price',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the service that owns the pricing.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the aircon type that owns the pricing.
     */
    public function airconType(): BelongsTo
    {
        return $this->belongsTo(AirconType::class);
    }

    /**
     * Scope a query to only include active pricing.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get pricing for a specific service and aircon type combination.
     */
    public static function getPricing($serviceId, $airconTypeId)
    {
        $pricing = self::where('service_id', $serviceId)
            ->where('aircon_type_id', $airconTypeId)
            ->where('is_active', true)
            ->first();

        if ($pricing) {
            return $pricing->price;
        }

        // Fallback to base service price if no specific pricing exists
        $service = Service::find($serviceId);
        return $service ? $service->base_price : 0;
    }
}
