<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'first_name', 'last_name',
        'user_type', 'status', 'referral_code', 'referred_by', 'last_activity'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // =================== RELATIONSHIPS ===================

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function passengerTrips()
    {
        return $this->hasMany(Trip::class, 'passenger_id');
    }

    public function driverTrips()
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    public function currentLocation()
    {
        return $this->hasOne(RealTimeLocation::class)->latest('location_timestamp');
    }

    public function favoriteLocations()
    {
        return $this->hasMany(FavoriteLocation::class);
    }

    public function givenRatings()
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications()
    {
        return $this->hasMany(PushNotification::class);
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    // =================== SCOPES ===================

    public function scopeDrivers($query)
    {
        return $query->whereIn('user_type', ['driver', 'both']);
    }

    public function scopePassengers($query)
    {
        return $query->whereIn('user_type', ['passenger', 'both']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithActiveSubscription($query)
    {
        return $query->whereHas('activeSubscription');
    }

    // =================== ACCESSORS & MUTATORS ===================

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => preg_replace('/[^0-9+]/', '', $value),
        );
    }

    // =================== METHODS ===================

    public function isDriver(): bool
    {
        return in_array($this->user_type, ['driver', 'both']);
    }

    public function isPassenger(): bool
    {
        return in_array($this->user_type, ['passenger', 'both']);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        $this->update(['referral_code' => $code]);
        return $code;
    }

    public function updateRating(): void
    {
        $avgRating = $this->receivedRatings()->avg('rating');
        $this->update([
            'rating_average' => round($avgRating, 1),
            'total_trips' => $this->passengerTrips()->where('trip_status', 'completed')->count() +
                           $this->driverTrips()->where('trip_status', 'completed')->count()
        ]);
    }

    public function isAvailableDriver(): bool
    {
        if (!$this->isDriver() || !$this->driver) {
            return false;
        }

        return $this->driver->driver_status === 'available' &&
               $this->driver->documents_verified &&
               $this->status === 'active';
    }

}
