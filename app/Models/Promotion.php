<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use PromotionUse;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description', 'discount_type', 'discount_value',
        'minimum_amount', 'max_uses', 'uses_per_user', 'start_date', 'end_date',
        'is_active', 'only_new_users'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'max_uses' => 'integer',
        'uses_per_user' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'only_new_users' => 'boolean',
    ];

    // =================== RELATIONSHIPS ===================

    public function promotionUses()
    {
        return $this->hasMany(PromotionUse::class);
    }

    // =================== SCOPES ===================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeValid($query)
    {
        return $query->active()
                    ->where(function($q) {
                        $q->whereNull('max_uses')
                          ->orWhereRaw('max_uses > (SELECT COUNT(*) FROM promotion_uses WHERE promotion_id = promotions.id)');
                    });
    }

    // =================== METHODS ===================

    public function canBeUsedBy(User $user): bool
    {
        // Check if promotion is active
        if (!$this->is_active || $this->start_date > now() || $this->end_date < now()) {
            return false;
        }

        // Check if only for new users
        if ($this->only_new_users && $user->total_trips > 0) {
            return false;
        }

        // Check max uses
        if ($this->max_uses && $this->promotionUses()->count() >= $this->max_uses) {
            return false;
        }

        // Check uses per user
        if ($this->uses_per_user) {
            $userUses = $this->promotionUses()->where('user_id', $user->id)->count();
            if ($userUses >= $this->uses_per_user) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($amount < $this->minimum_amount) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return ($amount * $this->discount_value) / 100;
        }

        return min($this->discount_value, $amount);
    }

    public function use(User $user, Trip $trip = null): PromotionUse
    {
        return PromotionUse::create([
            'promotion_id' => $this->id,
            'user_id' => $user->id,
            'trip_id' => $trip?->id,
        ]);
    }
}
