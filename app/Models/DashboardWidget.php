<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (user, widget_key) — the per-user dashboard selection.
 *
 * `is_enabled` lets super_admin toggle a widget on/off.
 * `position` controls the render order on the user's dashboard
 * (lower numbers render first).
 *
 * Use `DashboardResolver::widgetsForUser()` to turn these rows into
 * the actual rendered widget list — that helper falls back to the
 * registry's role defaults when no rows exist.
 */
class DashboardWidget extends Model
{
    protected $fillable = [
        'user_id',
        'widget_key',
        'position',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'position'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}