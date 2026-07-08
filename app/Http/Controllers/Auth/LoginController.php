<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
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
        $loginInput = $request->input('email');

        // Check if input is matric number or email
        $isMatricNumber = !filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        if ($isMatricNumber) {
            // Try to find user by matric number
            $user = User::where('matric_number', $loginInput)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => 'Matriculation number not found.',
                ]);
            }

            $credentials = [
                'email' => $user->email,
                'password' => $request->input('password'),
            ];
        } else {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
        }

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
            'email' => $isMatricNumber ? 'Invalid password for this matriculation number.' : 'The provided credentials do not match our records.',
        ]);
    }

    protected function authenticated(Request $request, $user)
    {
        // Get role slug safely - handle case where role might not be loaded
        $roleSlug = 'student';
        if ($user->role) {
            $roleSlug = $user->role->slug;
        }

        // Check if student needs to complete onboarding
        if ($roleSlug === 'student') {
            // Check if must change password
            if ($user->must_change_password) {
                return redirect()->route('student.password.change.required')
                    ->with('info', 'You must change your password before continuing.');
            }

            // Profile, biodata, and security question are OPTIONAL
            // Students can complete them later from their profile settings

            // Add login notification for students
            try {
                $loginNotification = Setting::get('login_notification');
                if ($loginNotification) {
                    session()->flash('login_notification', $loginNotification);
                }
            } catch (\Exception $e) {
                // Ignore settings errors
            }

            return redirect('/student/dashboard')->with('success', 'Welcome ' . $user->name . ', you are free to explore yourself.');
        }

        $redirectTo = match ($roleSlug) {
            'super_admin', 'admin' => '/admin/dashboard',
            'lecturer' => '/lecturer/dashboard',
            'hod' => '/hod/dashboard',
            'dean' => '/dean/dashboard',
            'registrar' => '/registrar/dashboard',
            'bursar' => '/bursar/dashboard',
            'applicant' => '/applicant/dashboard',
            default => '/dashboard',
        };

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