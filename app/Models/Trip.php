<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'passenger_id', 'driver_id', 'vehicle_id', 'trip_status', 'trip_type',
        'pickup_latitude', 'pickup_longitude', 'pickup_address',
        'destination_latitude', 'destination_longitude', 'destination_address',
        'requested_at', 'accepted_at', 'started_at', 'completed_at',
        'estimated_duration', 'actual_duration', 'base_fare', 'final_fare',
        'discount_applied', 'chasqui_commission', 'payment_method', 'payment_status',
        'distance_km', 'passenger_count', 'passenger_notes', 'driver_notes',
        'cancellation_reason'
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'destination_latitude' => 'decimal:8',
        'destination_longitude' => 'decimal:8',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'base_fare' => 'decimal:2',
        'final_fare' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'chasqui_commission' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'passenger_count' => 'integer',
    ];

    // =================== RELATIONSHIPS ===================

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function locations()
    {
        return $this->hasMany(RealTimeLocation::class, 'current_trip_id');
    }

    // =================== SCOPES ===================

    public function scopeActive($query)
    {
        return $query->whereIn('trip_status', ['requested', 'accepted', 'driver_arriving', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('trip_status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('requested_at', today());
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeForPassenger($query, $passengerId)
    {
        return $query->where('passenger_id', $passengerId);
    }

    // =================== METHODS ===================

    public function accept($driverId, $vehicleId): bool
    {
        if ($this->trip_status !== 'requested') {
            return false;
        }

        return $this->update([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'trip_status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function start(): bool
    {
        if ($this->trip_status !== 'accepted') {
            return false;
        }

        return $this->update([
            'trip_status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete($finalFare = null): bool
    {
        if ($this->trip_status !== 'in_progress') {
            return false;
        }

        $duration = $this->started_at ? now()->diffInMinutes($this->started_at) : null;

        return $this->update([
            'trip_status' => 'completed',
            'completed_at' => now(),
            'actual_duration' => $duration,
            'final_fare' => $finalFare ?? $this->base_fare,
        ]);
    }

    public function cancel($reason = null, $cancelledBy = null): bool
    {
        if (in_array($this->trip_status, ['completed', 'cancelled_by_passenger', 'cancelled_by_driver'])) {
            return false;
        }

        $status = $cancelledBy === 'passenger' ? 'cancelled_by_passenger' : 'cancelled_by_driver';

        return $this->update([
            'trip_status' => $status,
            'cancellation_reason' => $reason,
        ]);
    }

    public function calculateCommission(): float
    {
        $passenger = $this->passenger;
        $driver = $this->driver;

        // Verificar suscripciones activas para calcular comisión
        $passengerSubscription = $passenger->activeSubscription;
        $driverSubscription = $driver->activeSubscription ?? null;

        $commissionRate = 12.0; // Default rate

        if ($driverSubscription) {
            $commissionRate = $driverSubscription->subscriptionPlan->commission_percentage;
        }

        return ($this->final_fare * $commissionRate) / 100;
    }

    protected function driverAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->final_fare - $this->chasqui_commission,
        );
    }

    protected function duration(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->actual_duration ?? $this->estimated_duration,
        );
    }
}
