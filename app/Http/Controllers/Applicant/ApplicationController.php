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
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    public function dashboard()
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();
        return view('applicant.dashboard', compact('applicant'));
    }

    public function showApplicationForm()
    {
        // Check if admission form is open
        if (!SystemSetting::isOpen('admission_form_open')) {
            return view('applicant.closed', [
                'message' => 'Admission form is currently closed. Please check back later.'
            ]);
        }

        // Check if application fee is required
        $requireFee = SystemSetting::get(SystemSetting::ADMISSION_REQUIRE_FEE, 'false') === 'true';
        $feeAmount = SystemSetting::get(SystemSetting::ADMISSION_FEE_AMOUNT, 0);

        // Check if applicant has already paid
        $applicant = Applicant::where('user_id', auth()->id())->first();
        if ($requireFee && $feeAmount > 0 && (!$applicant || $applicant->payment_status !== 'completed')) {
            // Show payment required page
            return view('applicant.apply-payment', [
                'requireFee' => $requireFee,
                'feeAmount' => $feeAmount,
            ]);
        }

        $data = [
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
            'sessions' => Session::orderBy('name', 'desc')->get(),
            'states' => State::orderBy('name')->get(),
            'nationalities' => \App\Models\Nationality::all(),
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
            // Personal Information
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:applicants,email',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:100',
            'blood_group' => 'nullable|string|max:5',
            'genotype' => 'nullable|string|max:5',
            'disability' => 'nullable|in:none,physical,visual,hearing,other',
            'disability_details' => 'nullable|string|max:500',

            // Address
            'address' => 'required|string|max:500',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:local_governments,id',
            'nationality_id' => 'required|exists:nationalities,id',

            // Programme Selection
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',

            // O-Level Results
            'olevel1_subject1' => 'nullable|string|max:100',
            'olevel1_grade1' => 'nullable|string|max:5',
            'olevel1_subject2' => 'nullable|string|max:100',
            'olevel1_grade2' => 'nullable|string|max:5',
            'olevel1_subject3' => 'nullable|string|max:100',
            'olevel1_grade3' => 'nullable|string|max:5',
            'olevel1_subject4' => 'nullable|string|max:100',
            'olevel1_grade4' => 'nullable|string|max:5',
            'olevel1_subject5' => 'nullable|string|max:100',
            'olevel1_grade5' => 'nullable|string|max:5',
            'olevel1_exam_year' => 'nullable|integer|min:2000|max:2030',

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

            // Extra Curricular
            'extra_curricular' => 'nullable|string|max:1000',
        ]);

        // Handle file uploads
        if ($request->hasFile('passport')) {
            $validated['passport'] = $this->uploadFile($request->file('passport'), 'passports');
        }

        // Set email from authenticated user
        $validated['email'] = Auth::user()->email;
        $validated['user_id'] = Auth::id();
        $validated['application_number'] = Applicant::generateApplicationNumber();
        $validated['status'] = 'pending';

        $applicant = Applicant::create($validated);

        return redirect()->route('applicant.application')
            ->with('success', 'Application submitted successfully! Your Application Number is: ' . $applicant->application_number);
    }

    public function viewApplication()
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();

        // If no application exists, show a friendly message instead of 404
        if (!$applicant) {
            return view('applicant.application', compact('applicant'));
        }

        return view('applicant.application', compact('applicant'));
    }

    public function printApplication()
    {
        $applicant = Applicant::where('user_id', auth()->id())->firstOrFail();
        return view('applicant.print', compact('applicant'));
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

    private function uploadFile($file, $folder)
    {
        $filename = $folder . '_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');
        return $filename;
    }

    /**
     * Auto-create student after admission and payment
     */
    public static function createStudentFromApplicant(Applicant $applicant)
    {
        $role = Role::where('slug', 'student')->first();

        // Create user account
        $user = User::create([
            'name' => $applicant->full_name,
            'email' => $applicant->email,
            'password' => Hash::make($applicant->application_number),
            'role_id' => $role ? $role->id : 9,
            'is_active' => true,
        ]);

        // Generate matric number
        $matricNumber = self::generateMatricNumber($applicant);

        // Create student record
        $student = Student::create([
            'user_id' => $user->id,
            'matric_number' => $matricNumber,
            'school_id' => $applicant->school_id,
            'department_id' => $applicant->department_id,
            'programme_id' => $applicant->programme_id,
            'session_id' => $applicant->session_id,
            'level' => 1,
            'status' => 'active',
            'state_id' => $applicant->state_id,
            'lga_id' => $applicant->lga_id,
            'nationality_id' => $applicant->nationality_id,
        ]);

        // Update applicant
        $applicant->update([
            'status' => 'admitted',
            'student_created' => true,
            'matric_number' => $matricNumber,
        ]);

        return ['user' => $user, 'student' => $student, 'matric_number' => $matricNumber];
    }

    protected static function generateMatricNumber(Applicant $applicant)
    {
        $year = date('Y');
        $department = $applicant->department;
        $prefix = $department ? strtoupper(substr($department->name, 0, 3)) : 'APP';
        $count = Applicant::whereYear('created_at', $year)->where('id', '<=', $applicant->id)->count();
        return $year . '/' . $prefix . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}