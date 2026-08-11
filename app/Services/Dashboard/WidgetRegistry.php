<?php

namespace App\Services\Dashboard;

/**
 * Central catalogue of every dashboard widget the system knows about.
 *
 * Widgets are registered at boot time via `AppServiceProvider::boot()`
 * so the registry survives the request lifecycle without re-declaring
 * widgets in every dashboard controller.
 *
 * The registry is read-only after boot — there's no DB persistence here.
 * Per-user enable/disable lives in `dashboard_widgets` and is read by
 * `DashboardResolver`.
 */
class WidgetRegistry
{
    /**
     * @var array<string, WidgetDefinition>
     */
    private static array $widgets = [];

    /**
     * Add (or replace) a widget in the catalogue.
     */
    public static function register(WidgetDefinition $widget): void
    {
        self::$widgets[$widget->key] = $widget;
    }

    /**
     * Full catalogue, keyed by widget_key.
     *
     * @return array<string, WidgetDefinition>
     */
    public static function all(): array
    {
        return self::$widgets;
    }

    /**
     * All widgets eligible for the given role slug, in registration order.
     *
     * @return array<string, WidgetDefinition>
     */
    public static function forRole(string $roleSlug): array
    {
        return array_filter(
            self::$widgets,
            fn(WidgetDefinition $w) => $w->appliesTo($roleSlug)
        );
    }

    /**
     * Lookup a single widget by key. Returns null if unknown (which can
     * happen when a stored `dashboard_widgets.widget_key` references a
     * widget that's been removed in a later deploy — the resolver treats
     * those as no-ops).
     */
    public static function get(string $key): ?WidgetDefinition
    {
        return self::$widgets[$key] ?? null;
    }

    /**
     * Drop every registration. Test-only helper.
     */
    public static function flush(): void
    {
        self::$widgets = [];
    }
}