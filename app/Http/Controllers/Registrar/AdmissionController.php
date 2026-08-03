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
        $this->assertSameSchool($applicant);
        $applicant->load(['user', 'department', 'programme', 'school', 'session', 'state', 'lga', 'nationality']);
        return view('registrar.admission.show', compact('applicant'));
    }

    /**
     * Edit applicant
     */
    public function edit(Applicant $applicant)
    {
        $this->assertSameSchool($applicant);
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
        $this->assertSameSchool($applicant);
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
        $this->assertSameSchool($applicant);
        $applicant->delete();
        return redirect()->route('registrar.admission')->with('success', 'Applicant deleted successfully');
    }

    /**
     * Reset applicant password
     */
    public function resetPassword(Request $request, Applicant $applicant)
    {
        $this->assertSameSchool($applicant);
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
        $this->assertSameSchool($applicant);
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

    /**
     * Reserved-matric-number + user-creation helper called when the
     * registrar sets an applicant's status to `admitted`.
     *
     * NOTE: This method does NOT create the Student row any more.
     * The Student record (and the user→student role promotion) is created
     * lazily by ApplicantPaymentService::migrateApplicantToStudent when the
     * applicant pays the compulsory fee. See:
     * https://docs/... for the full flow.
     */
    protected function createStudentFromApplicant(Applicant $applicant)
    {
        DB::transaction(function () use ($applicant) {
            // Some bulk-uploaded admission-list rows may not have a User yet.
            // (Applicants who registered through /applicant/register already
            // have one, so we skip them.)
            if (! $applicant->user) {
                $role = Role::where('slug', 'student')->first();
                User::create([
                    'name' => $applicant->full_name,
                    'email' => $applicant->email,
                    'password' => Hash::make($applicant->application_number),
                    'role_id' => $role ? $role->id : 9,
                    'is_active' => false, // activated after compulsory fee
                ]);
            }

            // Reserve the matric. Migration to a real Student record happens
            // when the applicant pays the compulsory fee.
            $matricNumber = \App\Services\MatricNumberService::generate($applicant);

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
     * Show admission letter template editor
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
     * Save the editable admission letter template body.
     */
    public function uploadLetterTemplate(Request $request)
    {
        try {
            $body = (string) $request->input('template_body', '');
            SystemSetting::set('admission_letter_template', $body);

            return back()->with('success', 'Admission letter template saved successfully');
        } catch (\Throwable $e) {
            \Log::error('Template save failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to save template: ' . $e->getMessage());
        }
    }

    /**
     * Generate admission letters for admitted students
     */
    public function generateLetters(Request $request)
    {
        $departmentId = $request->department_id;

        $query = Applicant::where('status', 'admitted')->with(['user', 'department', 'school', 'programme', 'session']);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $admitted = $query->get();

        if ($admitted->isEmpty()) {
            return back()->with('error', 'No admitted students found for the selected filter.');
        }

        // If exactly one student, render the printable letter directly.
        if ($admitted->count() === 1) {
            return view('registrar.admission.letter', ['applicant' => $admitted->first()]);
        }

        // Otherwise show the batch index with links to each student's letter.
        return view('registrar.admission.letters-batch', compact('admitted', 'departmentId'));
    }

    /**
     * Generate single admission letter
     */
    public function generateLetter(Applicant $applicant)
    {
        $this->assertSameSchool($applicant);
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

    /**
     * Show the editable letter settings (body, fees, signature)
     */
    public function showLetterSettings()
    {
        return view('registrar.admission.letters');
    }

    /**
     * Save admission letter settings (body, fees, institution details, registrar name + signature)
     */
    public function saveLetterSettings(Request $request)
    {
        $request->validate([
            'registrar_name'      => 'nullable|string|max:255',
            'registrar_signature' => 'nullable|file|image|max:2048',
            'admission_letter_body' => 'nullable|string',
            'institution_name'    => 'nullable|string|max:255',
            'institution_address' => 'nullable|string|max:255',
            'institution_phone'   => 'nullable|string|max:50',
            'institution_email'   => 'nullable|email|max:255',
            'institution_website' => 'nullable|string|max:255',
            'fees.*.name'         => 'nullable|string|max:255',
            'fees.*.amount'       => 'nullable|numeric|min:0',
        ]);

        try {
            SystemSetting::set('admission_letter_body', $request->input('admission_letter_body', ''));

            $fees = $request->input('fees', []);
            $cleanFees = [];
            foreach ($fees as $fee) {
                $name = trim($fee['name'] ?? '');
                $amount = isset($fee['amount']) ? (float)$fee['amount'] : 0;
                if ($name !== '' && $amount > 0) {
                    $cleanFees[] = ['name' => $name, 'amount' => $amount];
                }
            }
            SystemSetting::set('admission_letter_fees', json_encode($cleanFees));

            $letterheadFields = [
                'institution_name', 'institution_address', 'institution_phone',
                'institution_email', 'institution_website',
            ];
            foreach ($letterheadFields as $field) {
                if ($request->has($field)) {
                    SystemSetting::set($field, $request->input($field, ''));
                }
            }

            // Registrar name — explicit setting preferred over the user-role lookup.
            $registrarName = trim((string) $request->input('registrar_name', ''));
            SystemSetting::set('registrar_name', $registrarName);

            // Handle signature upload
            if ($request->hasFile('registrar_signature')) {
                $file = $request->file('registrar_signature');
                $destination = public_path('storage/signatures');
                if (!is_dir($destination)) {
                    @mkdir($destination, 0755, true);
                }
                $ext = $file->getClientOriginalExtension();
                $filename = 'registrar_signature.' . $ext;
                $file->move($destination, $filename);
                SystemSetting::set('registrar_signature_path', 'signatures/' . $filename);
            }

            return back()->with('success', 'Letter settings saved successfully.');
        } catch (\Throwable $e) {
            \Log::error('saveLetterSettings failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to save letter settings: ' . $e->getMessage());
        }
    }

    /**
     * Delete the registrar signature
     */
    public function deleteSignature()
    {
        try {
            $existing = SystemSetting::get('registrar_signature_path');
            if ($existing) {
                $fullPath = public_path('storage/' . $existing);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                SystemSetting::set('registrar_signature_path', '');
            }
            return back()->with('success', 'Signature removed.');
        } catch (\Throwable $e) {
            \Log::error('deleteSignature failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to remove signature: ' . $e->getMessage());
        }
    }

    private function assertSameSchool(Applicant $applicant): void
    {
        $authUser = auth()->user();
        if (!$authUser) {
            abort(401);
        }
        if ($authUser->school_id
            && $applicant->school_id
            && (int) $applicant->school_id !== (int) $authUser->school_id) {
            abort(403, 'You are not allowed to access this applicant.');
        }
    }
}