<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Applicant::with(['user', 'department', 'programme', 'school']);

        // Search filter
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Department filter
        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        $applicants = $query->latest()->paginate(20);
        return view('registrar.admission.index', compact('applicants'));
    }

    /**
     * Show applicant details
     */
    public function show(Applicant $applicant)
    {
        $applicant->load(['user', 'department', 'programme', 'school', 'session', 'state', 'lga', 'nationality']);
        return view('registrar.admission.show', compact('applicant'));
    }

    /**
     * Edit applicant
     */
    public function edit(Applicant $applicant)
    {
        $applicant->load(['user', 'department', 'programme', 'school', 'session', 'centre', 'state', 'lga', 'nationality']);
        $data = [
            'applicant' => $applicant,
            'schools' => \App\Models\School::all(),
            'departments' => \App\Models\Department::all(),
            'programmes' => \App\Models\Programme::all(),
            'sessions' => \App\Models\Session::orderBy('name', 'desc')->get(),
            'states' => \App\Models\State::orderBy('name')->get(),
            'nationalities' => \App\Models\Nationality::all(),
            'centres' => \App\Models\AdmissionCentre::orderBy('name')->get(),
        ];
        return view('registrar.admission.edit', $data);
    }

    /**
     * Update applicant
     */
    public function update(Request $request, Applicant $applicant)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:applicants,email,' . $applicant->id,
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:Male,Female,Other',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',
            'centre_id' => 'required|exists:admission_centres,id',
        ]);

        $applicant->update($validated);

        return redirect()->route('registrar.admission')->with('success', 'Applicant updated successfully');
    }

    /**
     * Delete applicant
     */
    public function destroy(Applicant $applicant)
    {
        $applicant->delete();
        return redirect()->route('registrar.admission')->with('success', 'Applicant deleted successfully');
    }

    /**
     * Reset applicant password
     */
    public function resetPassword(Request $request, Applicant $applicant)
    {
        $request->validate([
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($applicant->user) {
            $applicant->user->update([
                'password' => Hash::make($request->new_password),
                'must_change_password' => true,
            ]);
            return back()->with('success', 'Password reset successfully');
        }

        return back()->with('error', 'User account not found');
    }

    public function updateStatus(Request $request, Applicant $applicant)
    {
        $request->validate([
            'status' => 'required|in:pending,reviewed,admitted,rejected',
        ]);

        $applicant->update(['status' => $request->status]);

        // If admitted, create student record (will be activated after payment)
        if ($request->status === 'admitted' && !$applicant->student_created) {
            $this->createStudentFromApplicant($applicant);
        }

        return back()->with('success', 'Admission status updated');
    }

    protected function createStudentFromApplicant(Applicant $applicant)
    {
        DB::transaction(function () use ($applicant) {
            $role = Role::where('slug', 'student')->first();

            // Create user account with application number as initial password
            $user = User::create([
                'name' => $applicant->full_name,
                'email' => $applicant->email,
                'password' => Hash::make($applicant->application_number),
                'role_id' => $role ? $role->id : 9,
                'is_active' => false, // Will be activated after payment
            ]);

            // Generate matric number
            $matricNumber = $this->generateMatricNumber($applicant);

            // Create student record
            $student = Student::create([
                'user_id' => $user->id,
                'matric_number' => $matricNumber,
                'school_id' => $applicant->school_id,
                'department_id' => $applicant->department_id,
                'programme_id' => $applicant->programme_id,
                'session_id' => Setting::get('session_id'),
                'level' => 1,
                'status' => 'pending', // Pending until payment
                'state_id' => $applicant->state_id ?? null,
                'lga_id' => $applicant->lga_id ?? null,
                'nationality_id' => $applicant->nationality_id ?? null,
                // Track that this student came from an application
                'from_application' => true,
                'applicant_id' => $applicant->id,
            ]);

            // Copy application payments to student payments
            $this->copyApplicationPaymentsToStudent($applicant, $student);

            $applicant->update([
                'student_created' => true,
                'matric_number' => $matricNumber,
            ]);
        });
    }

    /**
     * Copy payments from applicant to student record
     */
    protected function copyApplicationPaymentsToStudent(Applicant $applicant, Student $student)
    {
        // Get external payments made by this applicant
        $externalPayments = \App\Models\ExternalPayment::where('email', $applicant->email)
            ->where('payment_status', 'completed')
            ->where('is_used', true)
            ->get();

        foreach ($externalPayments as $extPayment) {
            // Check if payment already copied
            $existingPayment = \App\Models\Payment::where('reference', $extPayment->transaction_id)->first();

            if (!$existingPayment) {
                // Create payment record for student
                \App\Models\Payment::create([
                    'student_id' => $student->id,
                    'amount' => $extPayment->amount,
                    'reference' => $extPayment->transaction_id,
                    'transaction_id' => $extPayment->transaction_id,
                    'gateway' => 'external',
                    'status' => 'completed',
                    'payment_details' => json_encode([
                        'original_payment_id' => $extPayment->id,
                        'payment_type' => 'application_fee',
                        'copied_from' => 'applicant',
                    ]),
                    'student_type' => $applicant->category ?? 'fresh',
                    'is_verified' => true,
                    'fee_type' => 'application_fee',
                ]);
            }
        }

        // Also copy from applicant's own payment record if exists
        if ($applicant->payment_status === 'completed' && $applicant->payment_amount) {
            $existingPayment = \App\Models\Payment::where('reference', $applicant->payment_ref)->first();

            if (!$existingPayment) {
                \App\Models\Payment::create([
                    'student_id' => $student->id,
                    'amount' => $applicant->payment_amount,
                    'reference' => $applicant->payment_ref,
                    'transaction_id' => $applicant->payment_transaction_id ?? $applicant->payment_ref,
                    'gateway' => 'external',
                    'status' => 'completed',
                    'payment_details' => json_encode([
                        'copied_from' => 'applicant_record',
                    ]),
                    'student_type' => $applicant->category ?? 'fresh',
                    'is_verified' => true,
                    'fee_type' => 'application_fee',
                ]);
            }
        }
    }

    protected function generateMatricNumber(Applicant $applicant)
    {
        $year = date('Y');
        $department = $applicant->department;
        $prefix = $department ? strtoupper(substr($department->name, 0, 3)) : 'ADM';
        $count = Applicant::whereYear('created_at', $year)->where('id', '<=', $applicant->id)->count();
        return $year . '/' . $prefix . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls',
        ]);

        // Excel upload would be implemented here
        return back()->with('info', 'Bulk upload feature - implementation pending');
    }

    public function settings()
    {
        // Get system settings for admission (defensive: null if system_settings unavailable)
        $get = function ($key, $default = null) {
            try { return SystemSetting::get($key, $default); }
            catch (\Throwable $e) { \Log::warning('system_settings unavailable: ' . $e->getMessage()); return $default; }
        };

        $settings = [
            'admission_form_open' => $get('admission_form_open', 'false'),
            'admission_form_penalty' => $get('admission_form_penalty', 'false'),
            'admission_form_penalty_amount' => $get('admission_form_penalty_amount', 0),
            'admission_require_application_fee' => $get('admission_require_application_fee', 'false'),
            'admission_application_fee_amount' => $get('admission_application_fee_amount', 5000),
            'admission_accept_fee_amount' => $get('admission_accept_fee_amount', 10000),
            'admission_school_fee_amount' => $get('admission_school_fee_amount', 50000),
            'admission_letter_template' => $get('admission_letter_template'),
        ];

        return view('registrar.admission.settings', $settings);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'admission_form_open' => 'boolean',
            'admission_form_penalty' => 'boolean',
            'admission_form_penalty_amount' => 'nullable|numeric|min:0',
            'admission_require_application_fee' => 'boolean',
            'admission_application_fee_amount' => 'nullable|numeric|min:0',
            'admission_accept_fee_amount' => 'nullable|numeric|min:0',
            'admission_school_fee_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            // Update settings
            SystemSetting::set('admission_form_open', $request->boolean('admission_form_open') ? 'true' : 'false');
            SystemSetting::set('admission_form_penalty', $request->boolean('admission_form_penalty') ? 'true' : 'false');
            SystemSetting::set('admission_form_penalty_amount', $request->admission_form_penalty_amount ?? 0);
            SystemSetting::set('admission_require_application_fee', $request->boolean('admission_require_application_fee') ? 'true' : 'false');
            SystemSetting::set('admission_application_fee_amount', $request->admission_application_fee_amount ?? 5000);
            SystemSetting::set('admission_accept_fee_amount', $request->admission_accept_fee_amount ?? 10000);
            SystemSetting::set('admission_school_fee_amount', $request->admission_school_fee_amount ?? 50000);
        } catch (\Throwable $e) {
            \Log::error('updateSettings failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to save settings: ' . $e->getMessage());
        }

        return redirect()->route('registrar.admission.settings')->with('success', 'Admission settings updated successfully');
    }

    public function print()
    {
        $admitted = Applicant::where('status', 'admitted')->with('user', 'department')->get();
        return view('registrar.admission.print', compact('admitted'));
    }

    public function track(Request $request)
    {
        if (!$request->application_number) {
            return view('registrar.admission.track');
        }

        $applicant = Applicant::where('application_number', $request->application_number)
            ->orWhere('email', $request->application_number)
            ->first();

        return view('registrar.admission.track', compact('applicant'));
    }

    /**
     * Activate student after payment confirmation
     */
    public function activateStudent(Applicant $applicant)
    {
        $student = Student::where('matric_number', $applicant->matric_number)->first();
        if (!$student) {
            return back()->with('error', 'Student record not found.');
        }

        $student->update(['status' => 'active']);
        $student->user->update(['is_active' => true]);

        return back()->with('success', 'Student activated successfully!');
    }

    /**
     * Show admission letter template upload page
     */
    public function showLetterTemplate()
    {
        $template = null;
        try {
            $template = SystemSetting::get('admission_letter_template');
        } catch (\Throwable $e) {
            // system_settings may be unavailable (table missing, DB down, etc.).
            // Render the page anyway so the user gets a 200 with a clear form.
            \Log::warning('system_settings unavailable for showLetterTemplate: ' . $e->getMessage());
        }
        return view('registrar.admission.letter-template', compact('template'));
    }

    /**
     * Upload admission letter template
     */
    public function uploadLetterTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        try {
            // Create templates directory if it doesn't exist
            $destination = public_path('templates');
            if (!is_dir($destination)) {
                @mkdir($destination, 0755, true);
            }

            $file = $request->file('template');
            $filename = 'admission_letter_template.' . $file->getClientOriginalExtension();
            $file->move($destination, $filename);

            SystemSetting::set('admission_letter_template', $filename);

            return back()->with('success', 'Admission letter template uploaded successfully');
        } catch (\Throwable $e) {
            \Log::error('Template upload failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to upload template: ' . $e->getMessage());
        }
    }

    /**
     * Generate admission letters for admitted students
     */
    public function generateLetters(Request $request)
    {
        $departmentId = $request->department_id;

        $query = Applicant::where('status', 'admitted')->with(['user', 'department', 'school', 'programme']);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $admitted = $query->get();

        if ($admitted->isEmpty()) {
            return back()->with('info', 'No admitted students found.');
        }

        return view('registrar.admission.letters', compact('admitted'));
    }

    /**
     * Generate single admission letter
     */
    public function generateLetter(Applicant $applicant)
    {
        if ($applicant->status !== 'admitted') {
            return back()->with('error', 'Applicant is not admitted.');
        }

        $student = Student::where('matric_number', $applicant->matric_number)->first();

        return view('registrar.admission.letter', compact('applicant', 'student'));
    }

    /**
     * Upload admission list by department (bulk)
     */
    public function uploadAdmissionList(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:2048',
            'department_id' => 'required|exists:departments,id',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $count = 0;
        $errors = [];

        if ($extension === 'csv') {
            $data = array_map('str_getcsv', file($file));
            array_shift($data); // Remove header

            foreach ($data as $row) {
                if (empty($row[0])) continue;

                try {
                    $applicationNumber = trim($row[0]);
                    $status = trim($row[1] ?? 'admitted');

                    $applicant = Applicant::where('application_number', $applicationNumber)->first();

                    if (!$applicant) {
                        $errors[] = "Application not found: $applicationNumber";
                        continue;
                    }

                    if (strtolower($status) === 'admitted') {
                        $applicant->update(['status' => 'admitted']);

                        if (!$applicant->student_created) {
                            $this->createStudentFromApplicant($applicant);
                        }
                        $count++;
                    } else {
                        $applicant->update(['status' => $status]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error: " . $e->getMessage();
                }
            }
        }

        $message = "$count students admitted successfully";
        if (!empty($errors)) {
            $message .= ". Errors: " . implode('; ', array_slice($errors, 5));
        }

        return back()->with(empty($errors) ? 'success' : 'info', $message);
    }

    /**
     * View admission list by department
     */
    public function listByDepartment(Request $request)
    {
        $departments = \App\Models\Department::all();
        $departmentId = $request->department_id;

        $query = Applicant::where('status', 'admitted')
            ->with(['user', 'department', 'school', 'programme']);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $admitted = $query->latest()->get();

        return view('registrar.admission.list-by-dept', compact('admitted', 'departments', 'departmentId'));
    }

    /**
     * Show upload admission list page
     */
    public function showUploadByDepartment()
    {
        $departments = \App\Models\Department::with('school')->get();
        return view('registrar.admission.upload-list', compact('departments'));
    }
}