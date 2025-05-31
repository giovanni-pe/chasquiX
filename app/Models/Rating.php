<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id', 'rater_id', 'rated_id', 'rating', 'comment', 'rating_type'
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    // =================== RELATIONSHIPS ===================

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function rated()
    {
        return $this->belongsTo(User::class, 'rated_id');
    }

    // =================== SCOPES ===================

    public function scopeForUser($query, $userId)
    {
        return $query->where('rated_id', $userId);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeDriverRatings($query)
    {
        return $query->where('rating_type', 'passenger_to_driver');
    }

    public function scopePassengerRatings($query)
    {
        return $query->where('rating_type', 'driver_to_passenger');
    }

    // =================== METHODS ===================

    public static function createForTrip(Trip $trip, User $rater, int $rating, string $comment = null): self
    {
        $ratedId = $rater->id === $trip->passenger_id ? $trip->driver_id : $trip->passenger_id;
        $ratingType = $rater->id === $trip->passenger_id ? 'passenger_to_driver' : 'driver_to_passenger';

        $ratingRecord = self::create([
            'trip_id' => $trip->id,
            'rater_id' => $rater->id,
            'rated_id' => $ratedId,
            'rating' => $rating,
            'comment' => $comment,
            'rating_type' => $ratingType,
        ]);

        // Update user's average rating
        $ratedUser = User::find($ratedId);
        $ratedUser->updateRating();

        return $ratingRecord;
    }
}
