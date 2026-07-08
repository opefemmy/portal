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

        // Profile (biodata/guidance) is OPTIONAL - students can complete it later
        // Security question is OPTIONAL - students can set it later from their profile
        // Students have full access to the portal after password change

        // If there are required steps, redirect to the first one
        if (!empty($requiredSteps)) {
            $currentRoute = $request->route()->getName();

            $redirectMap = [
                'password' => 'student.password.change.required',
            ];

            // Allow access to all student routes
            $allowedRoutes = [
                'student.password.change.required',
                'student.password.change',
                'student.dashboard',
                'logout',
            ];

            // Check if current route is allowed
            if (!in_array($currentRoute, $allowedRoutes)) {
                // Redirect to password change if required
                $firstStep = $requiredSteps[0];
                if ($firstStep === 'password') {
                    return redirect()->route('student.password.change.required')
                        ->with('info', 'You must change your password before continuing.');
                }
            }
        }

        return $next($request);
    }
}