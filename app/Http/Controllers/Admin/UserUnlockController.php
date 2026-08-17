<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserUnlockController extends Controller
{
    use EnforcesPermission;

    /**
     * Show unlock user form — admin-only (auth gate on
     * `admin/users/unlock`). The same controller method
     * `showUnlockCode` is also reachable from the PUBLIC
     * `GET /unlock/{email}/{code}` route and is intentionally
     * NOT gated — guests need it for password reset.
     */
    public function showUnlockForm()
    {
        $this->requirePermission('admin.user-unlocks.manage');
        return view('admin.users.unlock');
    }

    /**
     * Generate unlock code for a user — admin-only.
     */
    public function generateUnlockCode(Request $request)
    {
        $this->requirePermission('admin.user-unlocks.manage');
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        // Generate unique unlock code
        $code = $user->generateUnlockCode();

        return back()->with('success', 'Unlock code generated!')
            ->with('unlock_code', $code)
            ->with('user_email', $user->email);
    }

    /**
     * Show unlock code display page — DUAL-USE: also reachable
     * via the PUBLIC `GET /unlock/{email}/{code}` route
     * (routes/web.php line 160). Intentionally NOT gated.
     */
    public function showUnlockCode(Request $request)
    {
        $code = $request->get('code');
        $email = $request->get('email');

        if (!$code || !$email) {
            return redirect()->route('admin.users.unlock')->with('error', 'Invalid request.');
        }

        return view('admin.users.unlock-code', compact('code', 'email'));
    }

    /**
     * Unlock user with new password — DUAL-USE: also reachable
     * via the PUBLIC `POST /unlock` route (routes/web.php line
     * 161). Intentionally NOT gated so guests can reset their
     * own password.
     */
    public function unlockUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'unlock_code' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        // Validate unlock code
        if (!$user->validateUnlockCode($request->unlock_code)) {
            return back()->with('error', 'Invalid or expired unlock code.');
        }

        // Set new password and clear unlock code
        $user->unlockWithNewPassword($request->new_password);

        return redirect()->route('login')->with('success', 'Password has been reset successfully. Please login with your new password.');
    }

    /**
     * Admin directly reset user password (no unlock code needed)
     * — admin-only.
     */
    public function resetUserPassword(Request $request)
    {
        $this->requirePermission('admin.user-unlocks.manage');
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail($request->user_id);

        // Generate a default password
        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);

        return back()->with('success', 'Password for ' . $user->email . ' has been reset successfully.');
    }

    /**
     * Quick unlock - admin directly sets password
     * — admin-only.
     */
    public function quickUnlock(Request $request)
    {
        $this->requirePermission('admin.user-unlocks.manage');
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        // Generate a temporary password
        $tempPassword = 'Temp' . rand(1000, 9999) . '@' . strtoupper(substr($user->name, 0, 2));

        $user->update([
            'password' => Hash::make($tempPassword),
            'password_changed_at' => now(),
            'must_change_password' => true,
        ]);

        return back()->with('success', "User unlocked! Temporary password: $tempPassword");
    }
}
