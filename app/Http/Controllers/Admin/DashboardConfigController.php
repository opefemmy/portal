<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardWidget;
use App\Models\User;
use App\Services\Dashboard\DashboardResolver;
use App\Services\Dashboard\WidgetRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Per-user dashboard widget configurator.
 *
 * `super_admin` can log in here and pick, per user, which widgets
 * that user sees. The selected widgets are persisted to
 * `dashboard_widgets` (one row per widget_key). The user's own
 * dashboard reads them via DashboardResolver.
 *
 * The configurator is deliberately JS-free — each widget is a row
 * with an `is_enabled` checkbox and a `position` number input.
 */
class DashboardConfigController extends Controller
{
    /**
     * Show the configurator for a single user.
     */
    public function edit(User $user)
    {
        $role = $user->role?->slug ?? '';

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
            'target'   => $user,
            'role'     => $role,
            'eligible' => $eligible,
            'existing' => $existing,
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
        $role = $user->role?->slug ?? '';
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
            ->route('admin.dashboard-config.edit', $user)
            ->with('success', "Dashboard for {$user->name} updated.");
    }
}