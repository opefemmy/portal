<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\ExternalPatient;
use App\Models\Hospital\HospitalPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExternalPortalController extends Controller
{
    /**
     * Show patient login page
     */
    public function showLogin()
    {
        return view('hospital.portal.login');
    }

    /**
     * Patient login with access code
     */
    public function login(Request $request)
    {
        $request->validate([
            'access_code' => 'required|string',
        ]);

        $patient = ExternalPatient::where('access_code', strtoupper($request->access_code))->first();

        if (!$patient) {
            return back()->with('error', 'Invalid access code. Please check and try again.');
        }

        if (!$patient->access_code_expires_at || now()->greaterThan($patient->access_code_expires_at)) {
            return back()->with('error', 'Your access code has expired. Please contact the hospital for a new code.');
        }

        // Update last login
        $patient->update(['last_login_at' => now()]);

        // Store in session
        session([
            'external_patient_logged_in' => true,
            'external_patient_id' => $patient->id,
            'external_patient_name' => $patient->full_name,
            'external_patient_number' => $patient->patient_number,
        ]);

        return redirect()->route('patient.dashboard');
    }

    /**
     * Patient Dashboard
     */
    public function dashboard()
    {
        $patient = $this->getAuthenticatedPatient();
        if (!$patient) {
            return redirect()->route('patient.login');
        }

        $patient->load(['visits', 'appointments', 'communications']);

        // Get pending payments
        $pendingPayments = HospitalPayment::where('patient_phone', $patient->phone)
            ->where('status', 'pending')
            ->get();

        // Get upcoming appointments
        $upcomingAppointments = $patient->appointments()
            ->where('appointment_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('appointment_date')
            ->limit(5)
            ->get();

        // Get recent visits
        $recentVisits = $patient->visits()
            ->orderBy('visit_date', 'desc')
            ->limit(5)
            ->get();

        return view('hospital.portal.dashboard', compact('patient', 'pendingPayments', 'upcomingAppointments', 'recentVisits'));
    }

    /**
     * Patient Medical History
     */
    public function medicalHistory()
    {
        $patient = $this->getAuthenticatedPatient();
        if (!$patient) {
            return redirect()->route('patient.login');
        }

        $patient->load(['visits' => function($q) {
            $q->orderBy('visit_date', 'desc');
        }, 'visits.prescriptions', 'visits.labOrders']);

        return view('hospital.portal.medical-history', compact('patient'));
    }

    /**
     * Patient Payments
     */
    public function payments()
    {
        $patient = $this->getAuthenticatedPatient();
        if (!$patient) {
            return redirect()->route('patient.login');
        }

        $payments = HospitalPayment::where('patient_phone', $patient->phone)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('hospital.portal.payments', compact('patient', 'payments'));
    }

    /**
     * Patient Appointments
     */
    public function appointments()
    {
        $patient = $this->getAuthenticatedPatient();
        if (!$patient) {
            return redirect()->route('patient.login');
        }

        $appointments = $patient->appointments()
            ->orderBy('appointment_date', 'desc')
            ->paginate(20);

        return view('hospital.portal.appointments', compact('patient', 'appointments'));
    }

    /**
     * Patient Communications
     */
    public function communications()
    {
        $patient = $this->getAuthenticatedPatient();
        if (!$patient) {
            return redirect()->route('patient.login');
        }

        $communications = $patient->communications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('hospital.portal.communications', compact('patient', 'communications'));
    }

    /**
     * Patient Profile
     */
    public function profile()
    {
        $patient = $this->getAuthenticatedPatient();
        if (!$patient) {
            return redirect()->route('patient.login');
        }

        return view('hospital.portal.profile', compact('patient'));
    }

    /**
     * Generate new access code
     */
    public function regenerateCode(Request $request)
    {
        $patient = $this->getAuthenticatedPatient();
        if (!$patient) {
            return redirect()->route('patient.login');
        }

        $newCode = strtoupper(Str::random(8));
        $patient->update([
            'access_code' => $newCode,
            'access_code_expires_at' => now()->addDays(30),
        ]);

        return back()->with('success', 'New access code generated: ' . $newCode);
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->forget(['external_patient_logged_in', 'external_patient_id', 'external_patient_name', 'external_patient_number']);
        return redirect()->route('patient.login')->with('success', 'Logged out successfully');
    }

    /**
     * Get authenticated patient from session
     */
    private function getAuthenticatedPatient()
    {
        if (!session('external_patient_logged_in')) {
            return null;
        }

        return ExternalPatient::find(session('external_patient_id'));
    }
}
