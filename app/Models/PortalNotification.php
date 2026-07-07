<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notification;

class PortalNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'is_read', 'read_at', 'link'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Create a new notification
     */
    public static function createNotification(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): self
    {
        return static::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
        ]);
    }

    /**
     * Send notification to multiple users
     */
    public static function notifyUsers(array $userIds, string $title, string $message, string $type = 'info'): void
    {
        foreach ($userIds as $userId) {
            static::createNotification($userId, $title, $message, $type);
        }
    }

    /**
     * Get unread notifications for a user
     */
    public static function unreadForUser(int $userId): int
    {
        return static::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }
}