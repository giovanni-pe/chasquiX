<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RealTimeLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'latitude', 'longitude', 'accuracy', 'speed', 'bearing',
        'is_driver_available', 'current_trip_id', 'location_timestamp'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'bearing' => 'decimal:2',
        'is_driver_available' => 'boolean',
        'location_timestamp' => 'datetime',
    ];

    // =================== RELATIONSHIPS ===================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentTrip()
    {
        return $this->belongsTo(Trip::class, 'current_trip_id');
    }

    // =================== SCOPES ===================

    public function scopeDriversAvailable($query)
    {
        return $query->where('is_driver_available', true);
    }

    public function scopeNearby($query, $lat, $lng, $radiusKm = 5)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->selectRaw("
            *, (
                {$earthRadius} * acos(
                    cos(radians({$lat})) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians({$lng})) +
                    sin(radians({$lat})) *
                    sin(radians(latitude))
                )
            ) AS distance
        ")
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance');
    }

    public function scopeRecent($query, $minutes = 5)
    {
        return $query->where('location_timestamp', '>=', now()->subMinutes($minutes));
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
