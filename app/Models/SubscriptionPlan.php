<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'target_user', 'monthly_price', 'annual_price',
        'max_trips_per_month', 'trip_discount_percentage', 'commission_percentage',
        'priority_requests', 'premium_support', 'advanced_analytics', 'is_active'
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'max_trips_per_month' => 'integer',
        'trip_discount_percentage' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'priority_requests' => 'boolean',
        'premium_support' => 'boolean',
        'advanced_analytics' => 'boolean',
        'is_active' => 'boolean',
    ];

    // =================== RELATIONSHIPS ===================

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscriptions()
    {
        return $this->hasMany(UserSubscription::class)->where('status', 'active');
    }

    // =================== SCOPES ===================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDrivers($query)
    {
        return $query->whereIn('target_user', ['driver', 'both']);
    }

    public function scopeForPassengers($query)
    {
        return $query->whereIn('target_user', ['passenger', 'both']);
    }

    // =================== METHODS ===================

    public function hasUnlimitedTrips(): bool
    {
        return $this->max_trips_per_month === -1;
    }

    public function getAnnualSavings(): float
    {
        if (!$this->annual_price) {
            return 0;
        }

        return ($this->monthly_price * 12) - $this->annual_price;
    }
}
