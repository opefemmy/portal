<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = ['user_id', 'title', 'message', 'type', 'is_read', 'link'];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public static function notify($user, $title, $message, $type = 'info', $link = null)
    {
        return $user->notifications()->create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
        ]);
    }
}