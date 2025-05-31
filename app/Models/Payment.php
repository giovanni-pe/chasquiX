<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'trip_id', 'subscription_id', 'amount', 'chasqui_commission',
        'driver_amount', 'payment_type', 'payment_method', 'payment_status',
        'external_reference', 'payment_details'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'chasqui_commission' => 'decimal:2',
        'driver_amount' => 'decimal:2',
        'payment_details' => 'array',
    ];

    // =================== RELATIONSHIPS ===================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function subscription()
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    // =================== SCOPES ===================

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    // =================== METHODS ===================

    public function markAsCompleted(): bool
    {
        return $this->update(['payment_status' => 'completed']);
    }

    public function markAsFailed(): bool
    {
        return $this->update(['payment_status' => 'failed']);
    }

    public static function createTripPayment(Trip $trip): self
    {
        $commission = $trip->calculateCommission();
        $driverAmount = $trip->final_fare - $commission;

        return self::create([
            'user_id' => $trip->passenger_id,
            'trip_id' => $trip->id,
            'amount' => $trip->final_fare,
            'chasqui_commission' => $commission,
            'driver_amount' => $driverAmount,
            'payment_type' => 'trip',
            'payment_method' => $trip->payment_method,
            'payment_status' => 'pending',
        ]);
    }
}
