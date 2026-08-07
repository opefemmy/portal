<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PasswordChangeController extends Controller
{
    public function showChangeForm()
    {
        return view('student.auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();
        $isForcedChange = $user && $user->must_change_password;

        // Validation rules — current_password is only required when the
        // student is changing voluntarily. The auto-login / first-login
        // flow sets `must_change_password = true` so the current-password
        // check is skipped: the user has no usable password yet, they
        // were just signed in via a signed URL from the registrar.
        $rules = [
            'new_password' => 'required|min:6|confirmed',
        ];
        if (! $isForcedChange) {
            $rules['current_password'] = 'required';
        }

        $request->validate($rules, [
            'new_password.min'      => 'Password must be at least 6 characters.',
            'new_password.confirmed' => 'Password confirmation does not match.',
        ]);

        // Verify current password when the change is voluntary.
        if (! $isForcedChange) {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
        }

        // Don't allow using matric number as password.
        $student = $user->student;
        if ($student && $request->new_password === $student->matric_number) {
            return back()->withErrors(['new_password' => 'Password cannot be your matriculation number.']);
        }

        // Update password.
        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);

        // For the forced-change flow (auto-login link), keep the student
        // signed in — they've just proven control of the URL. For the
        // voluntary change, force a re-login with the new password (the
        // previous behaviour: existing passwords are invalidated).
        if ($isForcedChange) {
            $request->session()->regenerate();
            return redirect()->route('student.dashboard')
                ->with('success', 'Password set. Welcome to your student portal.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Password changed successfully. Please login with your new password.');
    }
}