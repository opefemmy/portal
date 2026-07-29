<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PatientPortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('hospital_patient_id')) {
            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Please login to access the patient portal.'], 401);
            }
            return redirect()->route('patient-portal.login')
                ->with('error', 'Please login to access the patient portal.');
        }

        return $next($request);
    }
}
