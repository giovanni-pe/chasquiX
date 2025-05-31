<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id', 'vehicle_type', 'brand', 'model', 'year', 'license_plate',
        'color', 'passenger_capacity', 'insurance_valid', 'insurance_expiry',
        'technical_review', 'technical_review_expiry', 'vehicle_status', 'vehicle_photo_url'
    ];

    protected $casts = [
        'year' => 'integer',
        'passenger_capacity' => 'integer',
        'insurance_valid' => 'boolean',
        'insurance_expiry' => 'date',
        'technical_review' => 'boolean',
        'technical_review_expiry' => 'date',
    ];

    // =================== RELATIONSHIPS ===================

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    // =================== SCOPES ===================

    public function scopeActive($query)
    {
        return $query->where('vehicle_status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vehicle_type', $type);
    }

    // =================== METHODS ===================

    public function isDocumentsValid(): bool
    {
        return $this->insurance_valid &&
               $this->technical_review &&
               $this->insurance_expiry > now() &&
               $this->technical_review_expiry > now();
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->brand} {$this->model} ({$this->license_plate})";
    }
}
