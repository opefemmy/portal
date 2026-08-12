<?php

namespace App\Http\Controllers\DashboardConfig;

use App\Http\Controllers\Controller;
use App\Models\DashboardWidget;
use App\Models\User;
use App\Services\Dashboard\DashboardResolver;
use App\Services\Dashboard\WidgetRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Shared logic for every per-audience dashboard widget configurator.
 *
 * The actual route + view are identical across audiences; only the
 * audience slug and the dashboard route name differ. Subclasses
 * (one per audience) supply those two pieces via the abstract
 * `audienceRoles()` and `dashboardRouteName()` methods, and the
 * route file wires the URL prefix that surfaces them.
 *
 * Each subclass's URL is `/{audience-prefix}/dashboard-config/{user}`.
 * Cross-user configuration requires the configurator's audience to
 * include `super_admin` (or similar elevated role) — every subclass
 * already does, but the route middleware is what actually enforces
 * it. The configurator controller itself does not gate by role: any
 * authed user with a configurator route may reach it; the middleware
 * on the route definition is the gate.
 *
 * Self-configuration (every user can configure their OWN dashboard)
 * is the primary use case after the migration — the super_admin
 * cross-user path remains for administrators.
 */
abstract class AbstractDashboardConfigController extends Controller
{
    /**
     * Role slugs whose members may use this configurator. The first
     * element is treated as the audience role for resolving widgets
     * (passed to WidgetRegistry::forRole()).
     *
     * @return array<int, string>
     */
    abstract protected function audienceRoles(): array;

    /**
     * The route name of the audience's dashboard. Used for the
     * "Back to Dashboard" link in the configurator view.
     */
    abstract protected function dashboardRouteName(): string;

    /**
     * Primary role slug used to resolve the target user's eligible
     * widget list. Falls back to the first audience role.
     */
    protected function audienceRoleFor(User $user): string
    {
        return $user->role?->slug ?? $this->audienceRoles()[0];
    }

    /**
     * Show the configurator for a single user.
     */
    public function edit(User $user)
    {
        $role = $this->audienceRoleFor($user);

        // Eligible widgets for the target user's role. Empty list if
        // the role isn't in the registry.
        $eligible = DashboardResolver::widgetsForRole($role);

        // Existing rows for this user, keyed by widget_key. May be
        // empty (user never configured → unconfigured).
        $existing = DashboardWidget::where('user_id', $user->id)
            ->orderBy('position')
            ->get()
            ->keyBy('widget_key');

        return view('admin.dashboard-config', [
            'target'            => $user,
            'role'              => $role,
            'eligible'          => $eligible,
            'existing'          => $existing,
            'audienceRoles'     => $this->audienceRoles(),
            'dashboardRouteName'=> $this->dashboardRouteName(),
            'configUpdateRoute' => $this->configUpdateRouteName(),
        ]);
    }

    /**
     * Persist the configurator form for a single user.
     *
     * Body shape:
     *   widgets[<key>][is_enabled] = 'on' | absent
     *   widgets[<key>][position]   = int
     *
     * On submit we sync: any widget NOT present in the request is
     * removed; widgets in the request are upserted with their new
     * position / enabled flag.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $role = $this->audienceRoleFor($user);
        $eligible = WidgetRegistry::forRole($role);

        // Normalise incoming data. Missing widgets = disabled.
        $submitted = $request->input('widgets', []);
        $submittedKeys = array_keys($submitted);

        // Build a list of every widget_key that exists in the
        // registry for this role — anything older that has since been
        // removed from the registry is left alone (orphan row counts
        // as disabled by the resolver's `WidgetRegistry::get()` miss).
        $registryKeys = array_keys($eligible);

        // Delete rows whose key is missing entirely from the request.
        // A row that's in the request but with is_enabled=0 still
        // counts as "exists, just disabled" → leave the row so its
        // position is preserved for next time the admin re-enables it.
        $toDelete = array_diff($registryKeys, $submittedKeys);
        if (!empty($toDelete)) {
            DashboardWidget::where('user_id', $user->id)
                ->whereIn('widget_key', $toDelete)
                ->delete();
        }

        // Upsert whatever the form submitted.
        foreach ($submitted as $key => $row) {
            if (!in_array($key, $registryKeys, true)) {
                // Key came in but isn't eligible for the target's role
                // (e.g. stale form post) — ignore.
                continue;
            }

            $isEnabled = !empty($row['is_enabled']);
            $position  = isset($row['position']) ? max(0, (int) $row['position']) : 0;

            DashboardWidget::updateOrCreate(
                ['user_id' => $user->id, 'widget_key' => $key],
                ['is_enabled' => $isEnabled, 'position' => $position],
            );
        }

        return redirect()
            ->route($this->configEditRouteName(), $user)
            ->with('success', "Dashboard for {$user->name} updated.");
    }

    /**
     * Route name for the configurator's edit endpoint. Subclasses
     * override this when their configurator lives at a non-admin URL.
     */
    protected function configEditRouteName(): string
    {
        return 'admin.dashboard-config.edit';
    }

    /**
     * Route name for the configurator's update endpoint.
     */
    protected function configUpdateRouteName(): string
    {
        return 'admin.dashboard-config.update';
    }
}