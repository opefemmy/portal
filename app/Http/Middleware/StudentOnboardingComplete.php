<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Only apply to students
        if (!$user->isStudent()) {
            return $next($request);
        }

        // Check if onboarding is required
        $requiredSteps = [];

        // Step 1: Must change password (only if required)
        if ($user->must_change_password) {
            $requiredSteps[] = 'password';
        }

        // Step 2: Must complete guidance details (required)
        if (!$user->guidance_name || !$user->guidance_phone) {
            $requiredSteps[] = 'profile';
        }

        // Security question is OPTIONAL - not required for onboarding
        // Students can set it later from their profile

        // If there are required steps, redirect to the first one
        if (!empty($requiredSteps)) {
            $currentRoute = $request->route()->getName();

            $redirectMap = [
                'password' => 'student.password.change.required',
                'profile' => 'student.profile',
            ];

            // Allow access to the required step pages
            $allowedRoutes = [
                'student.password.change.required',
                'student.password.change',
                'student.profile',
                'student.profile.update',
                'student.profile.passport',
                'student.dashboard',
                'logout',
            ];

            // Check if current route is allowed
            if (!in_array($currentRoute, $allowedRoutes)) {
                // Redirect to first incomplete step
                $firstStep = $requiredSteps[0];
                $redirectRoute = $redirectMap[$firstStep] ?? 'student.dashboard';

                if ($firstStep === 'password') {
                    return redirect()->route($redirectRoute)
                        ->with('info', 'You must change your password before continuing.');
                } elseif ($firstStep === 'profile') {
                    return redirect()->route($redirectRoute)
                        ->with('info', 'Please complete your profile with guidance details.');
                }
            }
        }

        return $next($request);
    }
}