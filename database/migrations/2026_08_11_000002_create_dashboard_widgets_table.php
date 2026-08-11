<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user dashboard widget selection.
 *
 * One row per (user_id, widget_key). `is_enabled` lets super_admin toggle
 * a widget on/off; `position` controls render order on the user's dashboard.
 *
 * If a user has zero rows here, DashboardResolver falls back to all widgets
 * the registry exposes for the user's role — so unconfigured users see the
 * full role default until someone customises their dashboard.
 *
 * `cascadeOnDelete()` on `user_id` so deleting a user wipes their
 * dashboard configuration cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $t->string('widget_key', 80);
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_enabled')->default(true);
            $t->timestamps();

            // One row per (user, widget). Re-saving a config upserts.
            $t->unique(['user_id', 'widget_key']);
            // Fast lookup for the configured-but-enabled slice.
            $t->index(['user_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};