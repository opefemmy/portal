<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\ExternalPatient;
use App\Models\Hospital\ExternalAppointment;
use App\Models\Hospital\ExternalCommunication;
use App\Models\Hospital\HospitalVisit;
use App\Models\Hospital\HospitalPayment;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalServiceType;
use App\Models\Hospital\HospitalServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExternalPatientController extends Controller
{
    /**
     * List all external patients
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $patients = ExternalPatient::when($search, function($query) use ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('patient_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        })->orderBy('created_at', 'desc')->paginate(20);

        return view('hospital.external-patients.index', compact('patients', 'search'));
    }

    /**
     * Show patient details
     */
    public function show(ExternalPatient $patient)
    {
        $patient->load(['visits', 'appointments', 'communications']);
        $payments = HospitalPayment::where('patient_phone', $patient->phone)->get();

        return view('hospital.external-patients.show', compact('patient', 'payments'));
    }

    /**
     * Create new external patient
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
        ]);

        $patient = ExternalPatient::create([
            'patient_number' => ExternalPatient::generatePatientNumber(),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'full_name' => $request->first_name . ' ' . $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'address' => $request->address,
            'age' => $request->date_of_birth ? \Carbon\Carbon::parse($request->date_of_birth)->age : null,
        ]);

        // Create initial communication about registration
        ExternalCommunication::create([
            'patient_id' => $patient->id,
            'staff_id' => auth()->id(),
            'type' => 'registration',
            'subject' => 'Patient Registration',
            'message' => 'New external patient registered: ' . $patient->full_name,
        ]);

        return back()->with('success', 'Patient registered successfully! Patient Number: ' . $patient->patient_number);
    }

    /**
     * Update patient
     */
    public function update(Request $request, ExternalPatient $patient)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email',
            'gender' => 'nullable|in:male,female',
        ]);

        $patient->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'full_name' => $request->first_name . ' ' . $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'address' => $request->address,
        ]);

        return back()->with('success', 'Patient updated successfully!');
    }

    /**
     * Create new visit
     */
    public function createVisit(Request $request, ExternalPatient $patient)
    {
        $request->validate([
            'visit_type' => 'required|string',
            'chief_complaint' => 'nullable|string',
        ]);

        $visit = HospitalVisit::create([
            'patient_id' => $patient->id,
            'visit_number' => HospitalVisit::generateVisitNumber(),
            'visit_date' => now(),
            'visit_type' => $request->visit_type,
            'chief_complaint' => $request->chief_complaint,
            'created_by' => auth()->id(),
            'status' => 'in_progress',
        ]);

        // Create communication
        ExternalCommunication::create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'staff_id' => auth()->id(),
            'type' => 'visit',
            'subject' => 'New Visit',
            'message' => 'Visit started: ' . $visit->visit_number . ' - ' . ($request->chief_complaint ?? 'No complaint recorded'),
        ]);

        return redirect()->route('hospital.visits.edit', $visit->id);
    }

    /**
     * Schedule appointment
     */
    public function scheduleAppointment(Request $request, ExternalPatient $patient)
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'purpose' => 'required|string',
        ]);

        $appointment = ExternalAppointment::create([
            'patient_id' => $patient->id,
            'appointment_number' => ExternalAppointment::generateAppointmentNumber(),
            'appointment_date' => $request->appointment_date,
            'purpose' => $request->purpose,
            'department' => $request->department,
            'status' => 'scheduled',
        ]);

        // Create communication
        ExternalCommunication::create([
            'patient_id' => $patient->id,
            'staff_id' => auth()->id(),
            'type' => 'appointment',
            'subject' => 'Appointment Scheduled',
            'message' => 'Appointment scheduled for ' . $appointment->appointment_date->format('d M Y, h:i A') . ' - ' . $request->purpose,
        ]);

        return back()->with('success', 'Appointment scheduled successfully!');
    }

    /**
     * Send communication to patient
     */
    public function sendCommunication(Request $request, ExternalPatient $patient)
    {
        $request->validate([
            'type' => 'required|in:sms,email,call,note',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        ExternalCommunication::create([
            'patient_id' => $patient->id,
            'staff_id' => auth()->id(),
            'type' => $request->type,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return back()->with('success', 'Communication sent successfully!');
    }

    /**
     * Get patient by phone for quick lookup
     */
    public function lookup(Request $request)
    {
        $patient = ExternalPatient::where('phone', $request->phone)->first();

        if ($patient) {
            return response()->json([
                'success' => true,
                'patient' => [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'full_name' => $patient->full_name,
                    'phone' => $patient->phone,
                    'email' => $patient->email,
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Patient not found']);
    }

    // =====================================================
    // Patient Self-Service Portal Methods
    // =====================================================

    /**
     * Show patient portal landing page
     */
    public function portalIndex()
    {
        return view('hospital-portal.index');
    }

    /**
     * Show patient registration form
     */
    public function showPortalRegister()
    {
        return view('hospital-portal.register');
    }

    /**
     * Register new external patient (self-service)
     */
    public function registerPortal(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:hospital_external_patients,email',
            'phone' => 'required|string|max:20',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date|before:today',
            'blood_group' => 'nullable|string|max:5',
            'genotype' => 'nullable|string|max:5',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'allergies' => 'nullable|string|max:1000',
            'chronic_conditions' => 'nullable|string|max:1000',
        ]);

        // Generate patient number and access code
        $patientNumber = ExternalPatient::generatePatientNumber();
        $accessCode = strtoupper(Str::random(8));

        // Create patient record
        $patient = ExternalPatient::create([
            'patient_number' => $patientNumber,
            'access_code' => $accessCode,
            'access_code_expires_at' => Carbon::now()->addDays(30),
            'password' => null, // No password, access via code only
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'full_name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'gender' => $validated['gender'] ?? 'male',
            'date_of_birth' => $validated['date_of_birth'],
            'age' => $validated['date_of_birth'] ? Carbon::parse($validated['date_of_birth'])->age : null,
            'blood_group' => $validated['blood_group'],
            'genotype' => $validated['genotype'],
            'address' => $validated['address'],
            'emergency_contact_name' => $validated['emergency_contact_name'],
            'emergency_contact_phone' => $validated['emergency_contact_phone'],
            'allergies' => $validated['allergies'],
            'chronic_conditions' => $validated['chronic_conditions'],
            'is_active' => true,
        ]);

        return view('hospital-portal.registration-success', compact('patient'));
    }

    /**
     * Show patient login form (by access code)
     */
    public function showPortalLogin()
    {
        return view('hospital-portal.login');
    }

    /**
     * Login patient by access code
     */
    public function loginPortal(Request $request)
    {
        $request->validate([
            'patient_number' => 'required|string',
            'access_code' => 'required|string',
        ]);

        // Allow login by patient_number OR phone number
        $patient = ExternalPatient::where(function($query) use ($request) {
                $query->where('patient_number', $request->patient_number)
                      ->orWhere('phone', 'like', '%' . $request->patient_number);
            })
            ->where('access_code', $request->access_code)
            ->where('is_active', true)
            ->first();

        if (!$patient) {
            return back()->with('error', 'Invalid access code or patient number. Please check and try again.');
        }

        // Check if code is expired
        if ($patient->access_code_expires_at && Carbon::now()->greaterThan($patient->access_code_expires_at)) {
            // Generate new code
            $newCode = strtoupper(Str::random(8));
            $patient->update([
                'access_code' => $newCode,
                'access_code_expires_at' => Carbon::now()->addDays(30),
            ]);

            session(['hospital_patient_id' => $patient->id]);
            session(['hospital_patient_code' => $newCode]);

            return redirect()->route('patient-portal.dashboard')
                ->with('info', 'Your access code has expired. A new code has been generated. Please save it: ' . $newCode);
        }

        // Update last login
        $patient->update(['last_login_at' => Carbon::now()]);

        session(['hospital_patient_id' => $patient->id]);
        session(['hospital_patient_code' => $patient->access_code]);

        return redirect()->route('patient-portal.dashboard')
            ->with('success', 'Welcome back, ' . $patient->full_name . '!');
    }

    /**
     * Show patient dashboard
     */
    public function dashboardPortal()
    {
        $patientId = session('hospital_patient_id');

        if (!$patientId) {
            return redirect()->route('patient-portal.login')
                ->with('error', 'Please login to access your portal.');
        }

        $patient = ExternalPatient::findOrFail($patientId);

        // Get patient's payments
        $payments = HospitalPayment::where('patient_phone', $patient->phone)
            ->orWhere('patient_email', $patient->email)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get patient's appointments (by phone or email)
        $appointments = HospitalAppointment::where('patient_phone', $patient->phone)
            ->orWhere('patient_email', $patient->email)
            ->orderBy('appointment_date', 'desc')
            ->limit(10)
            ->get();

        // Get available services
        $services = HospitalServiceType::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        // Get pending service requests
        $serviceRequests = HospitalServiceRequest::where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('hospital-portal.dashboard', compact('patient', 'payments', 'appointments', 'services', 'serviceRequests'));
    }

    /**
     * Request a service (pre-payment)
     */
    public function requestServicePortal(Request $request)
    {
        $patientId = session('hospital_patient_id');

        if (!$patientId) {
            return redirect()->route('patient-portal.login');
        }

        $patient = ExternalPatient::findOrFail($patientId);

        $validated = $request->validate([
            'service_type_id' => 'required|exists:hospital_service_types,id',
            'appointment_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service = HospitalServiceType::findOrFail($validated['service_type_id']);

        // Calculate totals
        $portalCharge = ($service->amount * 2) / 100;
        $totalAmount = $service->amount + $portalCharge;

        // Create service request
        $serviceRequest = HospitalServiceRequest::create([
            'patient_id' => $patient->id,
            'service_type_id' => $service->id,
            'service_name' => $service->name,
            'category' => $service->category,
            'amount' => $service->amount,
            'portal_charge' => $portalCharge,
            'total_amount' => $totalAmount,
            'appointment_date' => $validated['appointment_date'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        return redirect()->route('patient-portal.dashboard')
            ->with('success', 'Service request submitted! Please proceed to payment. Your Service Code: ' . $serviceRequest->request_code);
    }

    /**
     * Make payment for service
     */
    public function initiatePaymentPortal(Request $request)
    {
        $patientId = session('hospital_patient_id');

        if (!$patientId) {
            return redirect()->route('patient-portal.login');
        }

        $patient = ExternalPatient::findOrFail($patientId);

        $validated = $request->validate([
            'service_type_id' => 'required|exists:hospital_service_types,id',
            'payment_method' => 'required|in:online,bank_transfer',
            'appointment_date' => 'nullable|date',
            'doctor_name' => 'nullable|string|max:255',
        ]);

        $service = HospitalServiceType::findOrFail($validated['service_type_id']);

        // Calculate portal charge
        $portalCharge = ($service->amount * 2) / 100;
        $totalAmount = $service->amount + $portalCharge;

        // Generate payment reference
        $paymentRef = 'HSP-' . strtoupper(Str::random(10));

        // Create payment record
        $payment = HospitalPayment::create([
            'payment_ref' => $paymentRef,
            'patient_name' => $patient->full_name,
            'patient_email' => $patient->email,
            'patient_phone' => $patient->phone,
            'patient_gender' => $patient->gender,
            'patient_age' => $patient->age,
            'service_type_id' => $service->id,
            'service_name' => $service->name,
            'amount' => $service->amount,
            'portal_charge' => $portalCharge,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'],
            'status' => 'pending',
            'payment_date' => Carbon::now()->toDateString(),
            'appointment_date' => $validated['appointment_date'] ? Carbon::parse($validated['appointment_date'])->format('Y-m-d H:i:s') : null,
            'doctor_name' => $validated['doctor_name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated!',
            'payment_id' => $payment->id,
            'reference' => $paymentRef,
            'amount' => $totalAmount,
        ]);
    }

    /**
     * Validate payment by reference
     */
    public function validatePaymentPortal(Request $request)
    {
        $request->validate([
            'payment_reference' => 'required|string',
        ]);

        $patientId = session('hospital_patient_id');
        $patient = $patientId ? ExternalPatient::find($patientId) : null;

        $payment = HospitalPayment::where('payment_ref', $request->payment_reference)
            ->when($patient, function($query) use ($patient) {
                return $query->where(function($q) use ($patient) {
                    $q->where('patient_phone', $patient->phone)
                      ->orWhere('patient_email', $patient->email);
                });
            })
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found. Please check your reference.',
            ]);
        }

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'reference' => $payment->payment_ref,
                'patient_name' => $payment->patient_name,
                'service_name' => $payment->service_name,
                'amount' => $payment->amount,
                'portal_charge' => $payment->portal_charge,
                'total_amount' => $payment->total_amount,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'created_at' => $payment->created_at->format('d M Y, h:i A'),
            ],
        ]);
    }

    /**
     * Logout patient
     */
    public function logoutPortal()
    {
        session()->forget(['hospital_patient_id', 'hospital_patient_code']);
        return redirect()->route('patient-portal.index')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * View payment receipt
     */
    public function viewReceiptPortal(HospitalPayment $payment)
    {
        $patientId = session('hospital_patient_id');

        // Verify ownership
        if ($patientId) {
            $patient = ExternalPatient::find($patientId);
            if ($patient && ($payment->patient_phone == $patient->phone || $payment->patient_email == $patient->email)) {
                return view('hospital-portal.receipt', compact('payment'));
            }
        }

        abort(403, 'Unauthorized access to this receipt.');
    }

    /**
     * Get patient profile
     */
    public function profilePortal()
    {
        $patientId = session('hospital_patient_id');

        if (!$patientId) {
            return redirect()->route('patient-portal.login');
        }

        $patient = ExternalPatient::findOrFail($patientId);
        return view('hospital-portal.profile', compact('patient'));
    }

    /**
     * Update patient profile
     */
    public function updateProfilePortal(Request $request)
    {
        $patientId = session('hospital_patient_id');

        if (!$patientId) {
            return redirect()->route('patient-portal.login');
        }

        $patient = ExternalPatient::findOrFail($patientId);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:hospital_external_patients,email,' . $patient->id,
            'phone' => 'required|string|max:20',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date|before:today',
            'blood_group' => 'nullable|string|max:5',
            'genotype' => 'nullable|string|max:5',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'allergies' => 'nullable|string|max:1000',
            'chronic_conditions' => 'nullable|string|max:1000',
        ]);

        $validated['full_name'] = $validated['first_name'] . ' ' . $validated['last_name'];
        $validated['age'] = $validated['date_of_birth'] ? Carbon::parse($validated['date_of_birth'])->age : $patient->age;

        $patient->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Generate new access code
     */
    public function regenerateCodePortal()
    {
        $patientId = session('hospital_patient_id');

        if (!$patientId) {
            return redirect()->route('patient-portal.login');
        }

        $patient = ExternalPatient::findOrFail($patientId);

        $newCode = strtoupper(Str::random(8));
        $patient->update([
            'access_code' => $newCode,
            'access_code_expires_at' => Carbon::now()->addDays(30),
        ]);

        return back()->with('success', 'New access code generated: ' . $newCode)->with('new_code', $newCode);
    }
}
