<?php

use App\Models\Promotion;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromotionUse extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id', 'user_id', 'trip_id'
    ];

    // =================== RELATIONSHIPS ===================

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
