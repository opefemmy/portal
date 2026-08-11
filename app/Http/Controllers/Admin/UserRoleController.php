<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Per-user multi-role assignment.
 *
 * Lets `super_admin` attach additional roles to a single user
 * (e.g. make `burtest` both `bursar` AND `cashier`). The primary
 * role — `users.role_id` — continues to drive RoleMiddleware and
 * the post-login redirect; the pivot `role_user` carries every
 * additional role the user holds.
 *
 * The form's `role_ids[]` array is the full set of role memberships
 * for the user after the save. `primary_role_id` picks which one of
 * those becomes the primary.
 */
class UserRoleController extends Controller
{
    /**
     * Replace the user's role memberships with the supplied set.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role_ids'        => 'required|array|min:1',
            'role_ids.*'      => 'integer|exists:roles,id',
            'primary_role_id' => 'required|integer|exists:roles,id',
        ]);

        // The primary role must be among the selected roles. This
        // protects against an admin un-checking the primary-role
        // checkbox and submitting — they'd lose their middleware
        // role on the next login. Promote a different role first.
        if (!in_array($data['primary_role_id'], $data['role_ids'], true)) {
            return back()
                ->withInput()
                ->with('error', 'The primary role must be one of the selected roles. Tick its checkbox and try again.');
        }

        // Sync the pivot with the new set. `sync` removes rows that
        // aren't in the request and adds new ones. The `booted()`
        // hook on User keeps `users.role_id` in the pivot, so after
        // sync we just set role_id and save — the hook inserts the
        // primary-role row into the pivot if it's somehow missing.
        $user->roles()->sync($data['role_ids']);

        if ($user->role_id !== $data['primary_role_id']) {
            $user->role_id = $data['primary_role_id'];
            $user->save();
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "{$user->name}'s roles updated.");
    }
}