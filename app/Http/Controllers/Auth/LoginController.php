<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Your account has been deactivated.',
                ]);
            }

            return $this->authenticated($request, $user);
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    protected function authenticated(Request $request, $user)
    {
        // Get role slug safely - handle case where role might not be loaded
        $roleSlug = 'student';
        if ($user->role) {
            $roleSlug = $user->role->slug;
        }

        $redirectTo = match ($roleSlug) {
            'super_admin', 'admin' => '/admin/dashboard',
            'student' => '/student/dashboard',
            'lecturer' => '/lecturer/dashboard',
            'hod' => '/hod/dashboard',
            'dean' => '/dean/dashboard',
            'registrar' => '/registrar/dashboard',
            'bursar' => '/bursar/dashboard',
            'applicant' => '/applicant/dashboard',
            default => '/dashboard',
        };

        // Add login notification for students
        if ($roleSlug === 'student') {
            try {
                $loginNotification = Setting::get('login_notification');
                if ($loginNotification) {
                    session()->flash('login_notification', $loginNotification);
                }

                $showPopup = Setting::get('show_post_login_popup');
                if ($showPopup) {
                    $postLoginMessage = Setting::get('post_login_message');
                    if ($postLoginMessage) {
                        session()->flash('show_popup', true);
                        session()->flash('popup_message', $postLoginMessage);
                    }
                }
            } catch (\Exception $e) {
                // Ignore settings errors
            }
        }

        return redirect($redirectTo)->with('success', 'Welcome back, ' . $user->name);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'You have been logged out successfully.');
    }
}