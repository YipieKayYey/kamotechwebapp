<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $booking_number
 * @property int $customer_id
 * @property string|null $customer_name
 * @property int $service_id
 * @property int $aircon_type_id
 * @property int $number_of_units
 * @property string|null $ac_brand
 * @property int|null $technician_id
 * @property \Illuminate\Support\Carbon $scheduled_start_at
 * @property \Illuminate\Support\Carbon $scheduled_end_at
 * @property int|null $estimated_duration_minutes
 * @property int|null $estimated_days
 * @property string $status
 * @property float $total_amount
 * @property string $payment_status
 * @property bool $use_custom_address
 * @property string|null $customer_address
 * @property string|null $province
 * @property string|null $city_municipality
 * @property string|null $barangay
 * @property string|null $house_no_street
 * @property string|null $customer_mobile
 * @property string|null $nearest_landmark
 * @property string|null $special_instructions
 * @property int|null $created_by
 * @property string|null $cancellation_reason
 * @property string|null $cancellation_details
 * @property \Illuminate\Support\Carbon|null $cancellation_requested_at
 * @property \Illuminate\Support\Carbon|null $cancellation_processed_at
 * @property int|null $cancellation_processed_by
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $customer
 * @property-read Service $service
 * @property-read AirconType $airconType
 * @property-read Technician|null $technician
 * @property-read User|null $createdBy
 * @property-read User|null $confirmedBy
 * @property-read User|null $cancellationProcessedBy
 * @property-read RatingReview|null $review
 * @property-read Earning|null $earning
 * @property-read string $service_location
 * @property-read string $full_address
 * @property-read string $display_name
 * @property int|null $promotion_id
 * @property float $discount_amount
 * @property float|null $original_amount
 * @property-read Promotion|null $promotion
 */
class Booking extends Model
{
    protected $fillable = [
        'booking_number',
        'customer_id',
        'guest_customer_id',
        'customer_name',
        'service_id',
        'aircon_type_id',
        'number_of_units',
        'ac_brand',
        'technician_id',
        'scheduled_start_at',
        'scheduled_end_at',
        'actual_start_at',
        'actual_end_at',
        'estimated_duration_minutes',
        'estimated_days',
        'status',
        'total_amount',
        'payment_status',
        'use_custom_address',
        'customer_address',
        'province',
        'city_municipality',
        'barangay',
        'house_no_street',
        'customer_mobile',
        'nearest_landmark',
        'special_instructions',
        'created_by',
        'cancellation_reason',
        'cancellation_details',
        'cancellation_requested_at',
        'cancellation_processed_at',
        'cancellation_processed_by',
        'rejection_reason',
        'confirmed_at',
        'confirmed_by',
        'completed_at',
        'promotion_id',
        'discount_amount',
        'original_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'use_custom_address' => 'boolean',
            'cancellation_requested_at' => 'datetime',
            'cancellation_processed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

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

    // Legacy timeslot relationship removed (dynamic scheduling)

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cancellationProcessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancellation_processed_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(RatingReview::class);
    }

    public function earning(): HasOne
    {
        return $this->hasOne(Earning::class);
    }

    public function guestCustomer(): BelongsTo
    {
        return $this->belongsTo(GuestCustomer::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Address Helper Methods
     */
    public function getServiceLocationAttribute(): string
    {
        // Always use booking address since users don't have saved addresses
        return $this->getBookingAddress();
    }

    public function getBookingAddress(): string
    {
        // Check if we have structured address components
        if ($this->hasBookingStructuredAddress()) {
            $addressParts = array_filter([
                $this->house_no_street,
                $this->barangay,
                $this->city_municipality,
                $this->province,
            ]);

            return ! empty($addressParts) ? implode(', ', $addressParts) : 'No address provided';
        }

        // Fallback to customer_address field
        return $this->customer_address ?? 'No address provided';
    }

    public function hasBookingStructuredAddress(): bool
    {
        return ! empty($this->province) || ! empty($this->city_municipality) ||
               ! empty($this->barangay) || ! empty($this->house_no_street);
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

    public function scopeCancelRequested($query)
    {
        return $query->where('status', 'cancel_requested');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_start_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('scheduled_start_at', '>=', today());
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopeRequiresUrgentAction($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'cancel_requested')
                ->orWhere(function ($subQ) {
                    $subQ->where('status', 'pending')
                        ->where('scheduled_start_at', '<=', now()->addDay());
                });
        });
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
            $this->province,
        ]);

        return ! empty($addressParts) ? implode(', ', $addressParts) : $this->customer_address;
    }

    public function getDisplayNameAttribute()
    {
        // Priority: registered customer > guest customer > customer_name field
        if ($this->customer) {
            return $this->customer->name;
        }

        if ($this->guestCustomer) {
            return $this->guestCustomer->full_name;
        }

        // Fallback to customer_name field for legacy bookings
        return $this->customer_name ?? 'Unknown Customer';
    }

    public function getIsGuestBookingAttribute()
    {
        // True if customer_name is provided (indicates admin booked for someone else)
        return ! empty($this->customer_name);
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'cancel_requested' => 'info',
            default => 'secondary'
        };
    }

    public function getPaymentStatusColorAttribute()
    {
        return match ($this->payment_status) {
            'pending' => 'warning',
            'paid' => 'success',
            'unpaid' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Cancellation Logic Methods
     */
    public function canCustomerRequestCancellation(): bool
    {
        // Must be more than 1 day before scheduled date
        if ($this->scheduled_start_at <= now()->addDay()) {
            return false;
        }

        // Can't request if already completed, cancelled, or request pending
        if (in_array($this->status, ['completed', 'cancelled', 'cancel_requested'])) {
            return false;
        }

        return true;
    }

    public function getCancellationDeadline(): \Carbon\Carbon
    {
        return $this->scheduled_start_at->copy()->subDay();
    }

    public function getTimeUntilCancellationDeadline(): string
    {
        if (! $this->canCustomerRequestCancellation()) {
            return 'Deadline passed';
        }

        return now()->diffForHumans($this->getCancellationDeadline(), true).' remaining';
    }

    public function hasPendingCancellationRequest(): bool
    {
        return $this->status === 'cancel_requested';
    }

    /**
     * Payment & Commission Methods
     */
    public function markAsPaid(): void
    {
        $this->update(['payment_status' => 'paid']);
        $this->calculateCommission();
    }

    public function markAsUnpaid(): void
    {
        $this->update(['payment_status' => 'unpaid']);
        $this->removeCommission();
    }

    public function createInitialEarning(): void
    {
        if ($this->technician) {
            $commissionRate = $this->technician->commission_rate / 100; // Convert percentage to decimal
            $commissionAmount = $this->total_amount * $commissionRate;

            // Add occasional bonuses for high-rated technicians
            $bonusAmount = 0;
            if ($this->technician->rating_average >= 4.8 && rand(1, 4) == 1) {
                $bonusAmount = round($commissionAmount * 0.1, 2); // 10% bonus
            }

            $totalEarning = $commissionAmount + $bonusAmount;

            // Create earning record based on current booking status
            $this->earning()->create([
                'technician_id' => $this->technician_id,
                'base_amount' => $this->total_amount,
                'commission_rate' => $this->technician->commission_rate, // Store as percentage
                'commission_amount' => $commissionAmount,
                'bonus_amount' => $bonusAmount,
                'total_amount' => $totalEarning,
                'payment_status' => $this->getEarningPaymentStatus(),
                'paid_at' => $this->getEarningPaidAt(),
            ]);
        }
    }

    public function updateEarningStatus(): void
    {
        if ($this->earning) {
            $this->earning->update([
                'payment_status' => $this->getEarningPaymentStatus(),
                'paid_at' => $this->getEarningPaidAt(),
            ]);
        }
    }

    private function getEarningPaymentStatus(): string
    {
        return match ($this->status) {
            'completed' => 'paid',
            'cancelled' => 'unpaid',
            'pending', 'confirmed', 'in_progress' => 'pending',
            default => 'pending'
        };
    }

    private function getEarningPaidAt(): ?string
    {
        if ($this->status === 'completed') {
            return $this->completed_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function removeCommission(): void
    {
        // Called when booking is cancelled
        $this->earning()?->delete();
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

                $booking->booking_number = 'KMT-'.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            }

            // Handle smart address system
            $booking->handleAddressGeneration();

            // Auto-calculate total amount based on service pricing
            if ($booking->service_id && $booking->aircon_type_id) {
                $booking->total_amount = $booking->calculateTotalAmount();
            }

            // Auto-calculate multi-day booking end date
            // Dynamic scheduling: scheduled_start_at / scheduled_end_at are set by creator
        });

        static::created(function ($booking) {
            // Auto-create earning record when technician is assigned
            if ($booking->technician_id && $booking->technician) {
                $booking->createInitialEarning();
            }
        });

        static::updating(function ($booking) {
            // Handle smart address system if address-related fields change
            if ($booking->isDirty(['use_custom_address', 'customer_id', 'province', 'city_municipality', 'barangay', 'house_no_street'])) {
                $booking->handleAddressGeneration();
            }

            // Recalculate total amount if service, aircon type, or promotion changes
            if ($booking->isDirty(['service_id', 'aircon_type_id', 'number_of_units', 'promotion_id'])) {
                $booking->total_amount = $booking->calculateTotalAmount();
            }

            // Handle status changes for payment and commission
            if ($booking->isDirty('status')) {
                if ($booking->status === 'completed') {
                    $booking->payment_status = 'paid';
                    $booking->completed_at = now();
                } elseif ($booking->status === 'cancelled') {
                    $booking->payment_status = 'unpaid';
                }
            }
        });

        static::updated(function ($booking) {
            // Create earning if technician was just assigned
            if ($booking->wasChanged('technician_id') && $booking->technician_id && ! $booking->earning) {
                $booking->createInitialEarning();
            }

            // Handle commission status updates
            if ($booking->wasChanged('status') && $booking->earning) {
                $booking->updateEarningStatus();
            }
        });
    }

    /**
     * Handle smart address generation based on use_custom_address toggle
     */
    private function handleAddressGeneration(): void
    {
        // Always generate address from booking's own components since users don't have saved addresses
        if ($this->hasBookingStructuredAddress()) {
            $this->customer_address = $this->getBookingAddress();
        }
    }

    /**
     * Calculate the total amount for this booking
     */
    public function calculateTotalAmount(): float
    {
        if (! $this->service_id || ! $this->aircon_type_id) {
            return 0;
        }

        // Get specific pricing for this service + aircon type combination
        $basePrice = \App\Models\ServicePricing::getPricing($this->service_id, $this->aircon_type_id);
        $numberOfUnits = $this->number_of_units ?? 0;

        // Simple pricing: base price × number of units
        $originalPrice = $basePrice * $numberOfUnits;

        // Apply promotion if exists
        if ($this->promotion_id && $this->promotion) {
            $promotion = $this->promotion;

            // Ensure promotion is still valid
            if ($promotion->isValid()) {
                $this->original_amount = $originalPrice;

                switch ($promotion->discount_type) {
                    case 'percentage':
                        $this->discount_amount = $originalPrice * ($promotion->discount_value / 100);
                        break;
                    case 'fixed':
                        $this->discount_amount = min($promotion->discount_value, $originalPrice);
                        break;
                    case 'free_service':
                        $this->discount_amount = $originalPrice;
                        break;
                    default:
                        $this->discount_amount = 0;
                }

                $totalPrice = max(0, $originalPrice - $this->discount_amount);
            } else {
                // Promotion no longer valid, clear it
                $this->promotion_id = null;
                $this->discount_amount = 0;
                $this->original_amount = null;
                $totalPrice = $originalPrice;
            }
        } else {
            $this->discount_amount = 0;
            $this->original_amount = null;
            $totalPrice = $originalPrice;
        }

        return round($totalPrice, 2);
    }

    /**
     * Calculate estimated days needed for this booking
     */
    public function calculateEstimatedDays(): int
    {
        if (! $this->service || ! $this->number_of_units) {
            return 1;
        }

        $service = $this->service;
        $numberOfUnits = $this->number_of_units;

        // Base days calculation based on service type
        $baseDays = match ($service->category) {
            'installation' => 2, // Installation takes longer
            'repair' => 1,
            'maintenance' => 1,
            'cleaning' => 1,
            default => 1
        };

        // Additional days for multiple units (every 3 units adds 1 day)
        if ($numberOfUnits > 3) {
            $baseDays += ceil(($numberOfUnits - 3) / 3);
        }

        return max(1, $baseDays);
    }

    /**
     * Check if this is a multi-day booking
     */
    public function isMultiDay(): bool
    {
        return $this->scheduled_end_at &&
               $this->scheduled_start_at &&
               $this->scheduled_end_at->toDateString() !== $this->scheduled_start_at->toDateString();
    }
}
