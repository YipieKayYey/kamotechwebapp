<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string|null $first_name
 * @property string|null $middle_initial
 * @property string|null $last_name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phone
 * @property string $role
 * @property bool $is_active
 * @property string|null $avatar
 * @property string|null $house_no_street
 * @property string|null $barangay
 * @property string|null $city_municipality
 * @property string|null $province
 * @property string|null $nearest_landmark
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read string $full_address
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', // Keep for backward compatibility
        'first_name',
        'middle_initial',
        'last_name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'house_no_street',
        'barangay',
        'city_municipality',
        'province',
        'nearest_landmark',
        'role',
        'is_active',
        'avatar',
        'google_id',
        'google_token',
        'google_refresh_token',
        'avatar_original',
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
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relationships
     */
    public function technician()
    {
        return $this->hasOne(Technician::class);
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute()
    {
        $name = $this->first_name;
        if ($this->middle_initial) {
            $name .= ' '.$this->middle_initial.'.';
        }
        if ($this->last_name) {
            $name .= ' '.$this->last_name;
        }

        return $name ?: $this->name; // Fallback to old name field
    }

    /**
     * Get the user's full address.
     */
    public function getFullAddressAttribute()
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
     * Set the name attribute (for backward compatibility)
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;

        // Parse name and set normalized fields if not already set
        if (! $this->first_name && ! $this->last_name) {
            $nameParts = explode(' ', trim($value));
            if (count($nameParts) > 0) {
                $this->attributes['first_name'] = $nameParts[0];

                if (count($nameParts) === 2) {
                    $this->attributes['last_name'] = $nameParts[1];
                } elseif (count($nameParts) >= 3) {
                    // Check if second part is a middle initial
                    if (strlen($nameParts[1]) <= 2) {
                        $this->attributes['middle_initial'] = str_replace('.', '', $nameParts[1]);
                        $this->attributes['last_name'] = implode(' ', array_slice($nameParts, 2));
                    } else {
                        $this->attributes['last_name'] = implode(' ', array_slice($nameParts, 1));
                    }
                }
            }
        }
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
