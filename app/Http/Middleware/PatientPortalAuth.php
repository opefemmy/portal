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
            return redirect()->route('patient-portal.login')
                ->with('error', 'Please login to access the patient portal.');
        }

        return $next($request);
    }
}
