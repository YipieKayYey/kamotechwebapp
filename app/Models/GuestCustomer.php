<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class GuestCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'middle_initial',
        'last_name',
        'phone',
        'email',
        'house_no_street',
        'barangay',
        'city_municipality',
        'province',
        'nearest_landmark',
        'notes',
        'total_bookings',
        'last_booking_date',
        'converted_to_user_id',
        'created_by',
    ];

    protected $casts = [
        'total_bookings' => 'integer',
        'last_booking_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the full name of the guest customer
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;
        if ($this->middle_initial) {
            $name .= ' '.$this->middle_initial.'.';
        }
        if ($this->last_name) {
            $name .= ' '.$this->last_name;
        }

        return $name;
    }

    /**
     * Get the full address of the guest customer
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->house_no_street,
            $this->barangay,
            $this->city_municipality,
            $this->province,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Relationships
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function convertedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_to_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Increment booking count and update last booking date
     */
    public function incrementBookingCount(): void
    {
        $this->increment('total_bookings');
        $this->update(['last_booking_date' => now()]);
    }

    /**
     * Check if guest can be converted to user
     */
    public function canConvertToUser(): bool
    {
        return ! $this->converted_to_user_id && $this->email;
    }

    /**
     * Convert guest customer to registered user
     */
    public function convertToUser(string $password): ?User
    {
        if (! $this->canConvertToUser()) {
            return null;
        }

        $user = User::create([
            'first_name' => $this->first_name,
            'middle_initial' => $this->middle_initial,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($password),
            'date_of_birth' => now()->subYears(25), // Default age
            'house_no_street' => $this->house_no_street,
            'barangay' => $this->barangay,
            'city_municipality' => $this->city_municipality,
            'province' => $this->province,
            'nearest_landmark' => $this->nearest_landmark,
            'role' => 'customer',
            'is_active' => true,
        ]);

        if ($user) {
            // Update guest customer record
            $this->update(['converted_to_user_id' => $user->id]);

            // Transfer all guest bookings to the new user
            $this->bookings()->update([
                'customer_id' => $user->id,
                'guest_customer_id' => null,
            ]);
        }

        return $user;
    }

    /**
     * Search scope for phone or name
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('phone', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
