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

        // Step 1: Must change password
        if ($user->must_change_password) {
            $requiredSteps[] = 'password';
        }

        // Step 2: Must set security question
        if (!$user->security_question) {
            $requiredSteps[] = 'security';
        }

        // Step 3: Must complete guidance details
        if (!$user->guidance_name || !$user->guidance_phone) {
            $requiredSteps[] = 'profile';
        }

        // If there are required steps, redirect to the first one
        if (!empty($requiredSteps)) {
            $currentRoute = $request->route()->getName();

            $redirectMap = [
                'password' => 'student.password.change.required',
                'security' => 'student.security.setup',
                'profile' => 'student.profile.edit',
            ];

            // Allow access to the required step pages
            $allowedRoutes = [
                'student.password.change.required',
                'student.password.change',
                'student.security.setup',
                'student.security.setup.store',
                'student.profile.edit',
                'student.profile.update',
                'student.profile.passport',
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
                } elseif ($firstStep === 'security') {
                    return redirect()->route($redirectRoute)
                        ->with('info', 'Please set a security question for password recovery.');
                } elseif ($firstStep === 'profile') {
                    return redirect()->route($redirectRoute)
                        ->with('info', 'Please complete your profile with guidance details.');
                }
            }
        }

        return $next($request);
    }
}