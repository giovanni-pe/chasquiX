<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'address', 'latitude', 'longitude', 'location_type'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // =================== RELATIONSHIPS ===================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // =================== SCOPES ===================

    public function scopeByType($query, $type)
    {
        return $query->where('location_type', $type);
    }

    public function scopeHome($query)
    {
        return $query->where('location_type', 'home');
    }

    public function scopeWork($query)
    {
        return $query->where('location_type', 'work');
    }

    // =================== METHODS ===================

    public function calculateDistanceTo($latitude, $longitude): float
    {
        $earthRadius = 6371; // kilometers

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDiff = $latTo - $latFrom;
        $lonDiff = $lonTo - $lonFrom;

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
