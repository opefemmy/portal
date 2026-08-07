<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\State;
use App\Models\LocalGovernment;
use App\Models\SystemSetting;
use App\Models\Student;
use App\Models\ExternalPayment;
use App\Models\AdmissionCentre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    /**
     * Canonical O'level subject list.
     *
     * The two compulsory subjects (English at position 1, Mathematics at
     * position 2) are also surfaced separately so the view layer can lock
     * them without having to know the order. Order in this array matches
     * the order the dropdown shows after the locked subjects.
     */
    public const OLEVEL_SUBJECTS = [
        'English',
        'Mathematics',
        'Physics',
        'Chemistry',
        'Biology',
        'Government',
        'Further Mathematics',
        'C.R.S',
        'I.R.K',
        'Economics',
        'Civic Education',
        'Computer Studies',
        'History',
        'Commerce',
        'Prin. of Account',
    ];

    public const OLEVEL_COMPULSORY = [
        1 => 'English',
        2 => 'Mathematics',
    ];

    /**
     * Render the applicant dashboard.
     *
     * Wrapped in a top-level Throwable catch so an unhandled exception in
     * the view (or any of the model accessors it calls — hasPaid,
     * nextPayablePurpose, transactionHistory, etc.) gets logged with full
     * detail instead of bubbling up to the bootstrap exception handler
     * and rendering a generic 500 page. The user lands on a useful error
     * page they can act on, and the technical team has the trace in
     * storage/logs/laravel.log to debug from.
     *
     * We render errors.500 directly rather than redirecting to the
     * dashboard because that would re-trigger the same exception —
     * if the dashboard can't render, redirecting to it produces a
     * redirect loop.
     */
    public function dashboard()
    {
        try {
            return $this->dashboardInner();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('applicant dashboard: uncaught error', [
                'user_id' => optional(auth()->user())->id,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->view('errors.500', ['exception' => $e], 500);
        }
    }

    private function dashboardInner()
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();

        // Get external payment if applicant exists
        $externalPayment = null;
        if ($applicant) {
            $externalPayment = ExternalPayment::where('applicant_id', $applicant->id)
                ->where('payment_status', 'completed')
                ->first();
        }

        return view('applicant.dashboard', compact('applicant', 'externalPayment'));
    }

    public function showApplicationForm()
    {
        // Check if admission form is open
        if (!SystemSetting::isOpen('admission_form_open')) {
            return view('applicant.closed', [
                'message' => 'Admission form is currently closed. Please check back later.'
            ]);
        }

        // Check if applicant has already submitted (any status beyond initial)
        $applicant = Applicant::where('user_id', auth()->id())->first();

        // If applicant has already submitted and is admitted, send them to pay acceptance fee
        if ($applicant && $applicant->status === 'admitted') {
            // If acceptance fee not yet paid, route to payment gateway
            if ($applicant->payment_status !== 'completed') {
                return redirect()->route('applicant.payment.gateway', ['purpose' => 'acceptance']);
            }
            // Already paid — go to dashboard to print letter
            return redirect()->route('applicant.dashboard')
                ->with('info', 'You have already been admitted and paid the acceptance fee.');
        }

        // Once the form has been filled in (status='pending' AND the
        // personal info fields are populated) the applicant cannot
        // re-open it. But an applicant can reach status='pending' via
        // verifyExternalPayment without ever filling the form — those
        // fields will be NULL. Allow them to come back and fill in the
        // form until they actually do.
        $formIsFilled = $applicant
            && !empty($applicant->gender)
            && !empty($applicant->date_of_birth)
            && !empty($applicant->address);
        if ($applicant && $applicant->status !== 'draft' && $formIsFilled) {
            return redirect()->route('applicant.application')
                ->with('info', 'You have already submitted your application. You cannot apply again.');
        }

        // Check if application fee is required
        $requireFee = SystemSetting::get(SystemSetting::ADMISSION_REQUIRE_FEE, 'false') === 'true';
        $feeAmount = SystemSetting::get(SystemSetting::ADMISSION_FEE_AMOUNT, 0);

        // Check if applicant has already paid
        if ($requireFee && $feeAmount > 0 && (!$applicant || $applicant->payment_status !== 'completed')) {
            // Show payment required page
            return view('applicant.apply-payment', [
                'requireFee' => $requireFee,
                'feeAmount' => $feeAmount,
            ]);
        }

        $data = [
            'applicant' => $applicant,
            'schools' => \Schema::hasTable('schools') ? School::all() : collect([]),
            'departments' => \Schema::hasTable('departments') ? Department::all() : collect([]),
            'programmes' => \Schema::hasTable('programmes') ? Programme::all() : collect([]),
            'sessions' => \Schema::hasTable('sessions') ? Session::where('is_current', true)->orderBy('name', 'desc')->get() : collect([]),
            'states' => \Schema::hasTable('states') ? State::orderBy('name')->get() : collect([]),
            'nationalities' => \Schema::hasTable('nationalities') ? \App\Models\Nationality::all() : collect([]),
            'centres' => \Schema::hasTable('admission_centres') ? \App\Models\AdmissionCentre::orderBy('name')->get() : collect([]),
            'olevelSubjects' => self::OLEVEL_SUBJECTS,
            'olevelCompulsory' => self::OLEVEL_COMPULSORY,
        ];
        return view('applicant.apply', $data);
    }

    /**
     * Initiate application fee payment
     */
    public function initiateApplicationFee(Request $request)
    {
        $requireFee = SystemSetting::get(SystemSetting::ADMISSION_REQUIRE_FEE, 'false') === 'true';
        $feeAmount = SystemSetting::get(SystemSetting::ADMISSION_FEE_AMOUNT, 0);

        if (!$requireFee || $feeAmount <= 0) {
            return back()->with('error', 'Application fee is not required.');
        }

        // Check if already paid
        $applicant = Applicant::where('user_id', auth()->id())->first();
        if ($applicant && $applicant->payment_status === 'completed') {
            return redirect()->route('applicant.apply')->with('success', 'Payment already completed.');
        }

        // Create payment reference
        $paymentRef = 'APPFEE-' . strtoupper(Str::random(10));

        // Store payment details temporarily (in session for now)
        session()->put('application_fee_ref', $paymentRef);
        session()->put('application_fee_amount', $feeAmount);

        // In a real implementation, this would integrate with a payment gateway
        // For now, we'll simulate payment by redirecting to a verification page
        return redirect()->route('applicant.apply.payment.verify', ['ref' => $paymentRef]);
    }

    /**
     * Show apply payment page - for making application fee payment.
     *
     * Wrapped in a top-level Throwable catch so a downstream error
     * (unrun migration, FK drift) never surfaces as a 500.
     */
    public function showApplyPayment(Request $request)
    {
        try {
            return $this->showApplyPaymentInner($request);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('apply payment page: uncaught error', [
                'user_id' => optional(auth()->user())->id,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('applicant.dashboard')
                ->with('error', 'We could not load the payment page. Please try again or contact the admissions office.');
        }
    }

    private function showApplyPaymentInner(Request $request)
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();

        // Resolve by purpose instead of by code so an admin who renames
        // the APP_FORM code (or creates a duplicate row with a different
        // code) still gets the right amount and label.
        $paymentType = \App\Models\PaymentType::findByPurpose(\App\Models\PaymentType::PURPOSE_APPLICATION);

        if (!$paymentType) {
            return back()->with('error', 'Application fee payment type not found.');
        }

        // Check if already paid — payment_status column may not exist on a
        // legacy deployment; Eloquent magic getter returns null in that
        // case, which is a safe 'not paid' state.
        if ($applicant && $applicant->payment_status === 'completed') {
            return redirect()->route('applicant.dashboard')->with('info', 'You have already paid the application fee.');
        }

        return view('applicant.apply-payment', compact('applicant', 'paymentType'));
    }

    /**
     * Process application fee payment.
     *
     * Wrapped in a top-level Throwable catch, mirror of showApplyPayment.
     */
    public function processApplyPayment(Request $request)
    {
        try {
            return $this->processApplyPaymentInner($request);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('apply payment process: uncaught error', [
                'user_id' => optional(auth()->user())->id,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('applicant.dashboard')
                ->with('error', 'We could not process your payment just now. Please try again or contact the admissions office.');
        }
    }

    private function processApplyPaymentInner(Request $request)
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();

        // Check if already paid
        if ($applicant && $applicant->payment_status === 'completed') {
            return redirect()->route('applicant.dashboard')->with('info', 'You have already paid the application fee.');
        }

        // Validate request
        $request->validate([
            'payment_type' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        // In production, integrate with payment gateway here
        // For now, store pending payment for manual verification
        if ($applicant) {
            $applicant->update([
                'payment_status' => 'pending',
                'payment_ref' => 'APP-' . strtoupper(Str::random(10)),
                'payment_amount' => $request->amount,
                'payment_date' => now(),
            ]);
        }

        return redirect()->route('applicant.dashboard')->with('success', 'Payment initiated. Please upload your payment proof for validation.');
    }

    /**
     * Simulate payment verification (in production, this would be callback from payment gateway)
     */
    public function verifyApplicationFee(Request $request)
    {
        $paymentRef = $request->get('ref');
        $requireFee = SystemSetting::get(SystemSetting::ADMISSION_REQUIRE_FEE, 'false') === 'true';
        $feeAmount = SystemSetting::get(SystemSetting::ADMISSION_FEE_AMOUNT, 0);

        if (!$requireFee || $feeAmount <= 0) {
            return redirect()->route('applicant.apply')->with('error', 'Payment not required.');
        }

        // Get or create applicant record
        $applicant = Applicant::where('user_id', auth()->id())->first();

        // For demonstration purposes, we'll mark as completed
        // In production, this would be triggered by payment gateway callback
        $applicantData = [
            'user_id' => auth()->id(),
            'email' => auth()->user()->email,
            'application_number' => Applicant::generateApplicationNumber(),
            'payment_status' => 'completed',
            'payment_ref' => $paymentRef,
            'payment_transaction_id' => 'TXN-' . Str::random(12),
            'payment_amount' => $feeAmount,
            'payment_date' => now(),
            'status' => 'pending',
        ];

        if (!$applicant) {
            $applicant = Applicant::create($applicantData);
        } else {
            $applicant->update($applicantData);
        }

        return redirect()->route('applicant.apply')->with('success', 'Payment successful! You can now complete your application.');
    }

    public function submitApplication(Request $request)
    {
        // Check if admission form is open
        if (!SystemSetting::isOpen('admission_form_open')) {
            return back()->with('error', 'Admission form is currently closed.');
        }

        $validated = $request->validate([
            // Personal Information - Required
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string|max:255',
            'religion' => 'required|string|max:100',
            'blood_group' => 'nullable|string|max:5',
            'genotype' => 'nullable|string|max:5',
            'disability' => 'nullable|in:none,physical,visual,hearing,other',
            'disability_details' => 'nullable|string|max:500',

            // Address - Required
            'address' => 'required|string|max:500',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:local_governments,id',
            'nationality_id' => 'required|exists:nationalities,id',

            // Programme Selection - Required
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',
            'centre_id' => 'required|exists:admission_centres,id',

            // O-Level Results - At least first sitting required
            'olevel1_subject1' => 'required|string|max:100',
            'olevel1_grade1' => 'required|string|max:5',
            'olevel1_subject2' => 'required|string|max:100',
            'olevel1_grade2' => 'required|string|max:5',
            'olevel1_subject3' => 'required|string|max:100',
            'olevel1_grade3' => 'required|string|max:5',
            'olevel1_subject4' => 'required|string|max:100',
            'olevel1_grade4' => 'required|string|max:5',
            'olevel1_subject5' => 'required|string|max:100',
            'olevel1_grade5' => 'required|string|max:5',
            'olevel1_exam_year' => 'required|integer|min:2000|max:2030',
            'olevel1_exam_type' => 'required|string|max:50',
            'olevel1_exam_number' => 'required|string|max:50',

            'olevel2_subject1' => 'nullable|string|max:100',
            'olevel2_grade1' => 'nullable|string|max:5',
            'olevel2_subject2' => 'nullable|string|max:100',
            'olevel2_grade2' => 'nullable|string|max:5',
            'olevel2_subject3' => 'nullable|string|max:100',
            'olevel2_grade3' => 'nullable|string|max:5',
            'olevel2_subject4' => 'nullable|string|max:100',
            'olevel2_grade4' => 'nullable|string|max:5',
            'olevel2_subject5' => 'nullable|string|max:100',
            'olevel2_grade5' => 'nullable|string|max:5',
            'olevel2_exam_year' => 'nullable|integer|min:2000|max:2030',
            'olevel2_exam_type' => 'nullable|string|max:50',
            'olevel2_exam_number' => 'nullable|string|max:50',

            // Guardian Information - Required
            'guardian_name' => 'required|string|max:255',
            'guardian_relationship' => 'required|string|max:50',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_address' => 'nullable|string|max:500',

            // Extra Curricular - Optional
            'extra_curricular' => 'nullable|string|max:1000',
        ]);

        // Handle file uploads
        if ($request->hasFile('passport')) {
            $validated['passport'] = $this->uploadFile($request->file('passport'), 'passports');
        }

        // Set email from authenticated user
        $validated['email'] = Auth::user()->email;
        $validated['user_id'] = Auth::id();
        $validated['status'] = 'pending';

        // Reuse the Applicant row seeded at signup instead of creating a
        // duplicate. updateOrCreate on user_id is the canonical
        // upsert key here — the signup-time seed used the same key in
        // firstOrCreate. Only generate an application_number when we
        // actually created the row (it is unique, so don't trample one
        // that already exists on resubmission paths).
        $existing = Applicant::where('user_id', Auth::id())->first();
        if ($existing && empty($existing->application_number)) {
            $validated['application_number'] = Applicant::generateApplicationNumber();
        } elseif (!$existing) {
            $validated['application_number'] = Applicant::generateApplicationNumber();
        }

        $applicant = Applicant::updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return redirect()->route('applicant.application')
            ->with('success', 'Application submitted successfully! Your Application Number is: ' . $applicant->application_number);
    }

    public function viewApplication()
    {
        // Eager load all relationships to prevent N/A issues
        $applicant = Applicant::with([
            'school',
            'department',
            'programme',
            'session',
            'state',
            'lga',
            'nationality',
            'user'
        ])->where('user_id', auth()->id())->first();

        // Block direct URL hits before the form has been submitted. The
        // dashboard disables the View button while in draft, but a
        // bookmarked link could bypass that. Sending them back to the
        // apply form keeps the path visible-and-meaningful.
        if ($applicant && $applicant->status === 'draft') {
            return redirect()->route('applicant.apply')
                ->with('error', 'Please complete and submit your application form before viewing it.');
        }

        // Get external payment if applicant exists
        $externalPayment = null;
        if ($applicant) {
            $externalPayment = ExternalPayment::where('applicant_id', $applicant->id)
                ->where('payment_status', 'completed')
                ->first();
        }

        // If no application exists, show a friendly message instead of 404
        if (!$applicant) {
            return view('applicant.application', compact('applicant'));
        }

        return view('applicant.application', compact('applicant', 'externalPayment'));
    }

    public function printApplication()
    {
        $userId = auth()->id();

        // Eager load all relationships to prevent N/A issues
        $applicant = Applicant::with([
            'school',
            'department',
            'programme',
            'session',
            'state',
            'lga',
            'nationality',
            'user'
        ])->where('user_id', $userId)->first();

        if (!$applicant) {
            return redirect()->route('applicant.dashboard')
                ->with('error', 'No application found. Please submit an application first.');
        }

        // Same guard as viewApplication: nothing to print until the form
        // has been submitted. Stops a bookmarked /applicant/application/print
        // URL from rendering a half-filled draft as a printable document.
        if ($applicant->status === 'draft') {
            return redirect()->route('applicant.apply')
                ->with('error', 'Please complete and submit your application form before printing it.');
        }

        // Get external payment if exists
        $externalPayment = null;
        if ($applicant) {
            $externalPayment = \App\Models\ExternalPayment::where('applicant_id', $applicant->id)
                ->where('payment_status', 'completed')
                ->first();
        }

        return view('applicant.print-simple', compact('applicant'));
    }

    public function checkStatus(Request $request)
    {
        $request->validate([
            'application_number' => 'required|string',
        ]);

        $applicant = Applicant::where('application_number', $request->application_number)
            ->orWhere('email', $request->application_number)
            ->first();

        if (!$applicant) {
            return back()->with('error', 'Application not found. Please check your application number.');
        }

        return view('applicant.status-check', compact('applicant'));
    }

    public function getDepartments($schoolId)
    {
        $departments = Department::where('school_id', $schoolId)->get();
        return response()->json($departments);
    }

    public function getLGAs($stateId)
    {
        $lgas = LocalGovernment::where('state_id', $stateId)->get();
        return response()->json($lgas);
    }

    public function getProgrammes($departmentId)
    {
        $programmes = Programme::where('department_id', $departmentId)->get();
        return response()->json($programmes);
    }

    /**
     * Edit application form
     */
    public function editApplication()
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();

        if (!$applicant) {
            return redirect()->route('applicant.apply')->with('error', 'No application found.');
        }

        // Only allow editing while the application is still a draft.
        // Once submitted (status='pending', 'admitted', or 'rejected')
        // the applicant is sent to the read-only view — the form cannot
        // be reopened. This matches the same status check on the apply
        // route so re-apply and re-edit are blocked together.
        // Exception: if payment was completed but the form has never
        // actually been filled in (gender / DOB / address are NULL), the
        // applicant can still come back to fill it in.
        $formIsFilled = !empty($applicant->gender)
            && !empty($applicant->date_of_birth)
            && !empty($applicant->address);
        if ($applicant->status !== 'draft' && $formIsFilled) {
            return redirect()->route('applicant.application')
                ->with('info', 'You have already submitted your application. You cannot edit it.');
        }

        $data = [
            'applicant' => $applicant,
            'schools' => \Schema::hasTable('schools') ? School::all() : collect([]),
            'departments' => \Schema::hasTable('departments') ? Department::all() : collect([]),
            'programmes' => \Schema::hasTable('programmes') ? Programme::all() : collect([]),
            'sessions' => \Schema::hasTable('sessions') ? Session::where('is_current', true)->orderBy('name', 'desc')->get() : collect([]),
            'states' => \Schema::hasTable('states') ? State::orderBy('name')->get() : collect([]),
            'nationalities' => \Schema::hasTable('nationalities') ? \App\Models\Nationality::all() : collect([]),
            'centres' => \Schema::hasTable('admission_centres') ? \App\Models\AdmissionCentre::orderBy('name')->get() : collect([]),
            'olevelSubjects' => self::OLEVEL_SUBJECTS,
            'olevelCompulsory' => self::OLEVEL_COMPULSORY,
        ];
        return view('applicant.apply-edit', $data);
    }

    /**
     * Update application
     */
    public function updateApplication(Request $request)
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();

        if (!$applicant) {
            return redirect()->route('applicant.apply')->with('error', 'No application found.');
        }

        // Same guard as editApplication: once submitted the applicant
        // cannot reopen the form to mutate it. The POST handler enforces
        // the same rule so the route can't be hit directly either.
        // Exception (same as editApplication): if the form has not been
        // actually filled in yet, allow the submit to populate it.
        $formIsFilled = !empty($applicant->gender)
            && !empty($applicant->date_of_birth)
            && !empty($applicant->address);
        if ($applicant->status !== 'draft' && $formIsFilled) {
            return redirect()->route('applicant.application')
                ->with('info', 'You have already submitted your application. You cannot edit it.');
        }

        $validated = $request->validate([
            // Personal Information - Required
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string|max:255',
            'religion' => 'required|string|max:100',
            'blood_group' => 'nullable|string|max:5',
            'genotype' => 'nullable|string|max:5',
            'disability' => 'nullable|in:none,physical,visual,hearing,other',
            'disability_details' => 'nullable|string|max:500',

            // Address - Required
            'address' => 'required|string|max:500',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:local_governments,id',
            'nationality_id' => 'required|exists:nationalities,id',

            // Programme Selection - Required
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',
            'centre_id' => 'required|exists:admission_centres,id',

            // O-Level Results - At least first sitting required
            'olevel1_subject1' => 'required|string|max:100',
            'olevel1_grade1' => 'required|string|max:5',
            'olevel1_subject2' => 'required|string|max:100',
            'olevel1_grade2' => 'required|string|max:5',
            'olevel1_subject3' => 'required|string|max:100',
            'olevel1_grade3' => 'required|string|max:5',
            'olevel1_subject4' => 'required|string|max:100',
            'olevel1_grade4' => 'required|string|max:5',
            'olevel1_subject5' => 'required|string|max:100',
            'olevel1_grade5' => 'required|string|max:5',
            'olevel1_exam_year' => 'required|integer|min:2000|max:2030',
            'olevel1_exam_type' => 'required|string|max:50',
            'olevel1_exam_number' => 'required|string|max:50',

            'olevel2_subject1' => 'nullable|string|max:100',
            'olevel2_grade1' => 'nullable|string|max:5',
            'olevel2_subject2' => 'nullable|string|max:100',
            'olevel2_grade2' => 'nullable|string|max:5',
            'olevel2_subject3' => 'nullable|string|max:100',
            'olevel2_grade3' => 'nullable|string|max:5',
            'olevel2_subject4' => 'nullable|string|max:100',
            'olevel2_grade4' => 'nullable|string|max:5',
            'olevel2_subject5' => 'nullable|string|max:100',
            'olevel2_grade5' => 'nullable|string|max:5',
            'olevel2_exam_year' => 'nullable|integer|min:2000|max:2030',
            'olevel2_exam_type' => 'nullable|string|max:50',
            'olevel2_exam_number' => 'nullable|string|max:50',

            // Guardian Information - Required
            'guardian_name' => 'required|string|max:255',
            'guardian_relationship' => 'required|string|max:50',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_address' => 'nullable|string|max:500',
            'guardian_relationship' => 'nullable|string|max:50',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_email' => 'nullable|email',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_address' => 'nullable|string|max:500',

            // Extra Curricular
            'extra_curricular' => 'nullable|string|max:1000',
        ]);

        // Handle file uploads
        if ($request->hasFile('passport')) {
            $validated['passport'] = $this->uploadFile($request->file('passport'), 'passports');
        }

        $applicant->update($validated);

        return redirect()->route('applicant.application')
            ->with('success', 'Application updated successfully!');
    }

    /**
     * Verify external payment
     */
    public function verifyExternalPayment(Request $request)
    {
        $request->validate([
            'payment_ref' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ]);

        $applicant = Applicant::where('user_id', auth()->id())->first();

        // Check if payment reference already used
        $existingApplicant = Applicant::where('payment_ref', $request->payment_ref)
            ->where('id', '!=', $applicant ? $applicant->id : 0)
            ->first();

        if ($existingApplicant) {
            return back()->with('error', 'This payment reference has already been used.');
        }

        // Get the required fee amount
        $requireFee = SystemSetting::get(SystemSetting::ADMISSION_REQUIRE_FEE, 'false') === 'true';
        $feeAmount = SystemSetting::get(SystemSetting::ADMISSION_FEE_AMOUNT, 0);

        if ($requireFee && $feeAmount > 0) {
            if ($request->amount < $feeAmount) {
                return back()->with('error', 'Amount is less than the required application fee of ₦' . number_format($feeAmount));
            }
        }

        $applicantData = [
            'user_id' => auth()->id(),
            'email' => auth()->user()->email,
            'application_number' => $applicant ? $applicant->application_number : Applicant::generateApplicationNumber(),
            'payment_status' => 'completed',
            'payment_ref' => $request->payment_ref,
            'payment_transaction_id' => 'EXT-' . Str::random(12),
            'payment_amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'status' => $applicant ? $applicant->status : 'pending',
        ];

        if (!$applicant) {
            Applicant::create($applicantData);
        } else {
            $applicant->update($applicantData);
        }

        return redirect()->route('applicant.application')
            ->with('success', 'Payment verified successfully! You can now submit your application.');
    }

    private function uploadFile($file, $folder)
    {
        $filename = $folder . '_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');
        return $filename;
    }

    /**
     * Print admission letter for the authenticated applicant.
     * Available only after the applicant has been admitted AND has paid
     * the acceptance fee.
     */
    public function printAdmissionLetter()
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();

        if (!$applicant || $applicant->status !== 'admitted') {
            return back()->with('error', 'You have not been admitted yet.');
        }

        if (! $applicant->hasPaid(\App\Models\PaymentType::PURPOSE_ACCEPTANCE)) {
            return back()->with('error', 'Please pay the acceptance fee before printing your admission letter.');
        }

        $applicant->load(['school', 'department', 'programme', 'session', 'state', 'lga']);
        $student = Student::where('matric_number', $applicant->matric_number)->first();

        return view('applicant.admission-letter', compact('applicant', 'student'));
    }

    /**
     * Show the unified transaction history for the authenticated applicant.
     */
    public function transactionHistory()
    {
        $applicant = Applicant::where('user_id', auth()->id())->firstOrFail();
        $history = $applicant->transactionHistory();

        return view('applicant.payments.history', [
            'applicant' => $applicant,
            'history' => $history,
        ]);
    }
}