<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'message', 'notification_type', 'is_read',
        'sent_at', 'read_at', 'additional_data'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'additional_data' => 'array',
    ];

    // =================== RELATIONSHIPS ===================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // =================== SCOPES ===================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    // =================== METHODS ===================

    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public static function createForUser(User $user, string $title, string $message, string $type, array $additionalData = []): self
    {
        return self::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'notification_type' => $type,
            'additional_data' => $additionalData,
            'sent_at' => now(),
        ]);
    }
}
