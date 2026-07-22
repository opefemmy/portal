<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication Service
 * Handles all authentication-related operations
 */
class AuthService
{
    /**
     * Attempt to login with credentials
     */
    public function login(string $loginInput, string $password, bool $isMaster = false): User
    {
        $user = $this->findUser($loginInput);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'User not found.',
            ]);
        }

        // Check master password (admin only)
        if ($isMaster) {
            return $this->attemptMasterLogin($user, $password);
        }

        // Regular login
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        // Check if account is active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact support.',
            ]);
        }

        return $user;
    }

    /**
     * Find user by email or matric number
     */
    public function findUser(string $loginInput): ?User
    {
        // Try email first
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $loginInput)->first();
        }

        // Try matric number
        return User::where('matric_number', $loginInput)->first();
    }

    /**
     * Attempt master password login
     */
    protected function attemptMasterLogin(User $user, string $password): User
    {
        $masterPassword = Setting::get('master_password', 'masteradmin2024');

        if ($password !== $masterPassword) {
            throw ValidationException::withMessages([
                'email' => 'Invalid master password.',
            ]);
        }

        // Check if user has admin role
        if (!$user->role || !in_array($user->role->slug, ['super_admin', 'admin', 'staff', 'ict_admin'])) {
            throw ValidationException::withMessages([
                'email' => 'Master password access is only available for admin users.',
            ]);
        }

        return $user;
    }

    /**
     * Log the user in
     */
    public function authenticate(User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);

        // Log the login activity
        $this->logLoginActivity($user);
    }

    /**
     * Log login activity
     */
    protected function logLoginActivity(User $user): void
    {
        try {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'description' => 'User logged in',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if logging fails
        }
    }

    /**
     * Logout the user
     */
    public function logout(): void
    {
        $user = Auth::user();

        if ($user) {
            try {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'logout',
                    'description' => 'User logged out',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            } catch (\Exception $e) {
                // Silently fail
            }
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Get redirect URL based on user role
     */
    public function getRedirectUrl(User $user): string
    {
        $roleSlug = $user->role?->slug ?? 'student';

        return match ($roleSlug) {
            'super_admin', 'admin', 'ict_admin' => '/admin/dashboard',
            'student' => '/student/dashboard',
            'lecturer' => '/lecturer/dashboard',
            'hod' => '/hod/dashboard',
            'dean' => '/dean/dashboard',
            'registrar' => '/registrar/dashboard',
            'bursar', 'finance', 'accountant', 'auditor' => '/bursar/dashboard',
            'librarian' => '/librarian/dashboard',
            'doctor', 'nurse', 'pharmacist', 'lab_scientist', 'cmd', 'hospital_receptionist' => '/hospital/dashboard',
            'rector', 'executive' => '/executive/dashboard',
            'business_committee' => '/business-committee/dashboard',
            'academic_board' => '/academic-board/dashboard',
            'applicant' => '/applicant/dashboard',
            'staff' => '/admin/dashboard',
            default => '/dashboard',
        };
    }

    /**
     * Check if student needs onboarding
     */
    public function needsOnboarding(User $user): bool
    {
        if ($user->role?->slug !== 'student') {
            return false;
        }

        return $user->must_change_password;
    }

    /**
     * Get onboarding route
     */
    public function getOnboardingRoute(User $user): string
    {
        return route('student.password.change.required');
    }

    /**
     * Validate user credentials without logging in
     */
    public function validateCredentials(string $loginInput, string $password): bool
    {
        $user = $this->findUser($loginInput);

        if (!$user || !$user->is_active) {
            return false;
        }

        return Hash::check($password, $user->password);
    }

    /**
     * Check rate limit status
     */
    public function isRateLimited(string $key): bool
    {
        $lockedUntil = cache()->get($key . ':locked_until');

        return $lockedUntil && now()->lessThan($lockedUntil);
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(string $key): int
    {
        $maxAttempts = 5;
        $attempts = cache()->get($key . ':attempts', 0);

        return max(0, $maxAttempts - $attempts);
    }
}