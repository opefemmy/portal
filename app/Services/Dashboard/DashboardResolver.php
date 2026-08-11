<?php

namespace App\Services\Dashboard;

use App\Models\DashboardWidget;
use App\Models\User;

/**
 * Combines the widget registry with the per-user `dashboard_widgets`
 * table to produce the final list of widgets a user should see on
 * their dashboard.
 *
 * Resolution rules (in order):
 *
 *  1. If the user has at least one row in `dashboard_widgets`, those
 *     rows win. `is_enabled=false` rows are filtered out. Unknown
 *     `widget_key` values (e.g. from a removed widget) are silently
 *     skipped. The remaining widgets are ordered by `position` ASC.
 *
 *  2. Otherwise, fall back to the registry's role defaults — every
 *     widget the registry exposes for the user's role slug, in
 *     registration order. This means an unconfigured user sees the
 *     full role dashboard, identical to the pre-configurator behaviour.
 *
 * The returned array is a list of `[definition, data]` tuples so the
 * caller can `@include` the partial with the data already resolved.
 */
class DashboardResolver
{
    /**
     * Resolve the widgets a given user should see.
     *
     * @return array<int, array{definition: WidgetDefinition, data: mixed}>
     */
    public static function widgetsForUser(User $user): array
    {
        $role = $user->role?->slug ?? '';

        $rows = DashboardWidget::where('user_id', $user->id)
            ->orderBy('position')
            ->get();

        if ($rows->isNotEmpty()) {
            return self::fromRows($rows);
        }

        // No per-user config → role default (registry order).
        return self::fromRegistry($role);
    }

    /**
     * Eligible widgets for a role, regardless of per-user config.
     *
     * Used by the configurator UI to populate the checkbox list when
     * a user has zero rows in `dashboard_widgets` (so the admin sees
     * every widget they could turn on, not just the ones already on).
     *
     * @return array<string, WidgetDefinition>
     */
    public static function widgetsForRole(string $roleSlug): array
    {
        return WidgetRegistry::forRole($roleSlug);
    }

    /**
     * @param \Illuminate\Support\Collection<int, DashboardWidget> $rows
     * @return array<int, array{definition: WidgetDefinition, data: mixed}>
     */
    private static function fromRows(\Illuminate\Support\Collection $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!$row->is_enabled) {
                continue;
            }
            $def = WidgetRegistry::get($row->widget_key);
            if ($def === null) {
                // Stale key — widget removed in a later deploy. Skip.
                continue;
            }
            $out[] = [
                'definition' => $def,
                'data'       => ($def->data)(),
            ];
        }
        return $out;
    }

    /**
     * @return array<int, array{definition: WidgetDefinition, data: mixed}>
     */
    private static function fromRegistry(string $role): array
    {
        $out = [];
        foreach (WidgetRegistry::forRole($role) as $def) {
            $out[] = [
                'definition' => $def,
                'data'       => ($def->data)(),
            ];
        }
        return $out;
    }
}