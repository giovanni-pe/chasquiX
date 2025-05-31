<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'subscription_plan_id', 'status', 'starts_at', 'expires_at',
        'monthly_price', 'discount_applied', 'payment_method', 'auto_renewal', 'cancelled_at'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'monthly_price' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'auto_renewal' => 'boolean',
    ];

    // =================== RELATIONSHIPS ===================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    // =================== SCOPES ===================

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
                    ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    // =================== METHODS ===================

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at > now();
    }

    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renewal' => false,
        ]);
    }

    public function renew(): bool
    {
        if (!$this->auto_renewal) {
            return false;
        }

        return $this->update([
            'starts_at' => $this->expires_at,
            'expires_at' => $this->expires_at->addMonth(),
        ]);
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->expires_at, false));
    }
}
