<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'license_number', 'license_expiry_date', 'experience_years',
        'driver_status', 'documents_verified', 'background_check', 'verified_at',
        'total_earnings', 'completed_trips'
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'documents_verified' => 'boolean',
        'background_check' => 'boolean',
        'verified_at' => 'datetime',
        'total_earnings' => 'decimal:2',
        'completed_trips' => 'integer',
        'experience_years' => 'integer',
    ];

    // =================== RELATIONSHIPS ===================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function activeVehicle()
    {
        return $this->hasOne(Vehicle::class)->where('vehicle_status', 'active');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'driver_id', 'user_id');
    }

    public function earnings()
    {
        return $this->hasMany(Payment::class, 'user_id', 'user_id')
                    ->where('payment_type', 'trip');
    }

    // =================== SCOPES ===================

    public function scopeAvailable($query)
    {
        return $query->where('driver_status', 'available')
                    ->where('documents_verified', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('documents_verified', true)
                    ->where('background_check', true);
    }

    public function scopeOnline($query)
    {
        return $query->whereIn('driver_status', ['available', 'busy']);
    }

    // =================== METHODS ===================

    public function setAvailable(): bool
    {
        if (!$this->documents_verified || !$this->activeVehicle) {
            return false;
        }

        return $this->update(['driver_status' => 'available']);
    }

    public function setOffline(): bool
    {
        return $this->update(['driver_status' => 'offline']);
    }

    public function setBusy(): bool
    {
        return $this->update(['driver_status' => 'busy']);
    }

    public function canAcceptTrips(): bool
    {
        return $this->driver_status === 'available' &&
               $this->documents_verified &&
               $this->activeVehicle !== null;
    }

    public function addEarnings(float $amount): void
    {
        $this->increment('total_earnings', $amount);
        $this->increment('completed_trips');
    }

    protected function licenseExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->license_expiry_date < now(),
        );
    }
}
