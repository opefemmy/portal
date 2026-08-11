<?php

namespace App\Services\Dashboard;

/**
 * Value-object describing a single dashboard widget.
 *
 * - `key` is a stable identifier used in the `dashboard_widgets` table.
 *   Changing a key orphans existing user configurations, so it must be
 *   permanent for the lifetime of the widget.
 * - `label` is what the configurator UI shows.
 * - `type` hints how to render — `stat`, `table`, `list`, `action`,
 *   `progress`. Future PRs may gate partial choice on type.
 * - `appliesToRoles` is the list of role slugs the widget is eligible
 *   for. The configurator only shows these; the resolver only returns
 *   widgets whose role list includes the user's role. If empty, the
 *   widget is available to every role.
 * - `data` is a Closure that returns the data the partial needs
 *   (typically an array like `['label' => ..., 'value' => ..., 'icon' => ...]`
 *   for a stat tile, or a Collection for a table).
 * - `partial` is the Blade partial path (relative to `resources/views/`).
 *   Use `widgets.stat-card`, `widgets.table-card`, etc.
 */
final class WidgetDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type,
        public readonly array $appliesToRoles,
        public readonly \Closure $data,
        public readonly string $partial,
    ) {}

    /**
     * Whether this widget applies to the given role slug.
     *
     * If `appliesToRoles` is empty, the widget is universal (any role).
     */
    public function appliesTo(string $roleSlug): bool
    {
        return $this->appliesToRoles === []
            || in_array($roleSlug, $this->appliesToRoles, true);
    }
}