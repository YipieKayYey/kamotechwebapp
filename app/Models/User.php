<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $province
 * @property string|null $city_municipality
 * @property string|null $barangay
 * @property string|null $house_no_street
 * @property string $role
 * @property bool $is_active
 * @property string|null $avatar
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $service_location
 * @property-read string $formatted_address
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'province',
        'city_municipality',
        'barangay',
        'house_no_street',
        'role',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Address Helper Methods
     */
    public function getServiceLocationAttribute(): string
    {
        // Return structured address if available
        if ($this->hasStructuredAddress()) {
            return $this->formatted_address;
        }

        // Fallback to legacy address field
        return $this->address ?? 'No address provided';
    }

    public function getFormattedAddressAttribute(): string
    {
        if (! $this->hasStructuredAddress()) {
            return $this->address ?? 'No address provided';
        }

        $addressParts = array_filter([
            $this->house_no_street,
            $this->barangay,
            $this->city_municipality,
            $this->province,
        ]);

        return ! empty($addressParts) ? implode(', ', $addressParts) : ($this->address ?? 'No address provided');
    }

    public function hasStructuredAddress(): bool
    {
        return ! empty($this->province) || ! empty($this->city_municipality) ||
               ! empty($this->barangay) || ! empty($this->house_no_street);
    }

    /**
     * Relationships
     */
    public function technician()
    {
        return $this->hasOne(Technician::class);
    }

    public function bookingsAsCustomer()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function bookingsCreated()
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function customNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }

    public function reviewsAsCustomer()
    {
        return $this->hasMany(RatingReview::class, 'customer_id');
    }

    /**
     * Scopes
     */
    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }

    public function scopeTechnicians($query)
    {
        return $query->where('role', 'technician');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access based on role and panel
        if ($panel->getId() === 'admin') {
            return $this->role === 'admin' && $this->is_active;
        }
        
        if ($panel->getId() === 'technician') {
            return $this->role === 'technician' && $this->is_active;
        }
        
        return false;
    }
}
