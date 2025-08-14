<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'booking_number',
        'customer_id',
        'customer_name',
        'service_id',
        'aircon_type_id',
        'number_of_units',
        'ac_brand',
        'technician_id',
        'scheduled_date',
        'scheduled_end_date',
        'timeslot_id',
        'estimated_duration_minutes',
        'estimated_days',
        'status',
        'total_amount',
        'payment_status',
        'customer_address',
        'province',
        'city_municipality',
        'barangay',
        'house_no_street',
        'customer_mobile',
        'nearest_landmark',
        'special_instructions',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function airconType(): BelongsTo
    {
        return $this->belongsTo(AirconType::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }



    public function timeslot(): BelongsTo
    {
        return $this->belongsTo(Timeslot::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(RatingReview::class);
    }

    public function earning(): HasOne
    {
        return $this->hasOne(Earning::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', today());
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Accessors & Mutators
     */
    public function getFullAddressAttribute()
    {
        $addressParts = array_filter([
            $this->house_no_street,
            $this->barangay,
            $this->city_municipality,
            $this->province
        ]);
        
        return !empty($addressParts) ? implode(', ', $addressParts) : $this->customer_address;
    }

    public function getDisplayNameAttribute()
    {
        // If customer_name is provided (hybrid booking), use it
        if ($this->customer_name) {
            return $this->customer_name;
        }
        
        // Otherwise use the related customer's name
        return $this->customer ? $this->customer->name : 'Guest Customer';
    }

    public function getIsGuestBookingAttribute()
    {
        // True if customer_name is provided (indicates admin booked for someone else)
        return !empty($this->customer_name);
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    public function getPaymentStatusColorAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'warning',
            'paid' => 'success',
            'refunded' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                // Generate sequential booking number like KMT-000001, KMT-000002
                $lastBookingNumber = static::whereNotNull('booking_number')
                    ->where('booking_number', 'like', 'KMT-%')
                    ->orderByDesc('id')
                    ->pluck('booking_number')
                    ->first();
                
                if ($lastBookingNumber) {
                    // Extract number and increment
                    $lastNumber = (int) str_replace('KMT-', '', $lastBookingNumber);
                    $newNumber = $lastNumber + 1;
                } else {
                    // Start from 1 if no bookings exist
                    $newNumber = 1;
                }
                
                $booking->booking_number = 'KMT-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            }
            
            // Auto-generate complete address from components
            if (empty($booking->customer_address) || $booking->customer_address === 'Will be generated when booking is saved') {
                $booking->customer_address = $booking->full_address;
            }
            
            // Auto-calculate total amount based on service pricing
            if ($booking->service_id && $booking->aircon_type_id) {
                $booking->total_amount = $booking->calculateTotalAmount();
            }
        });

        static::updating(function ($booking) {
            // Auto-generate complete address if any component changes
            if ($booking->isDirty(['province', 'city_municipality', 'barangay', 'house_no_street'])) {
                $booking->customer_address = $booking->full_address;
            }
            
            // Recalculate total amount if service or aircon type changes
            if ($booking->isDirty(['service_id', 'aircon_type_id', 'number_of_units'])) {
                $booking->total_amount = $booking->calculateTotalAmount();
            }
        });
    }

    /**
     * Calculate the total amount for this booking
     */
    public function calculateTotalAmount(): float
    {
        if (!$this->service_id || !$this->aircon_type_id) {
            return 0;
        }

        // Get specific pricing for this service + aircon type combination
        $basePrice = \App\Models\ServicePricing::getPricing($this->service_id, $this->aircon_type_id);
        $numberOfUnits = $this->number_of_units ?? 0;
        
        // Apply multi-unit pricing with discounts
        if ($numberOfUnits == 1) {
            $totalPrice = $basePrice;
        } elseif ($numberOfUnits <= 3) {
            // 10% discount for 2-3 units
            $totalPrice = $basePrice * $numberOfUnits * 0.9;
        } elseif ($numberOfUnits <= 5) {
            // 15% discount for 4-5 units
            $totalPrice = $basePrice * $numberOfUnits * 0.85;
        } else {
            // 20% discount for 6+ units
            $totalPrice = $basePrice * $numberOfUnits * 0.8;
        }
        
        return round($totalPrice, 2);
    }
}
