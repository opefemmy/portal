<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Concerns\ResolvesRegistrarSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Applicant;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdmissionController extends Controller
{
    use ResolvesRegistrarSignature;
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('registrar.admissions.view');

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
        $this->requirePermission('registrar.admissions.view');
        $this->assertSameSchool($applicant);
        $applicant->load(['user', 'department', 'programme', 'school', 'session', 'state', 'localGovernment', 'nationalityRecord']);
        return view('registrar.admission.show', compact('applicant'));
    }

    /**
     * Edit applicant
     */
    public function edit(Applicant $applicant)
    {
        $this->requirePermission('registrar.applicants.edit');
        $this->assertSameSchool($applicant);
        $applicant->load(['user', 'department', 'programme', 'school', 'session', 'centre', 'state', 'localGovernment', 'nationalityRecord']);
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
        $this->requirePermission('registrar.applicants.edit');
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
        $this->requirePermission('registrar.applicants.edit');
        $this->assertSameSchool($applicant);
        $applicant->delete();
        return redirect()->route('registrar.admission')->with('success', 'Applicant deleted successfully');
    }

    /**
     * Reset applicant password
     */
    public function resetPassword(Request $request, Applicant $applicant)
    {
        $this->requirePermission('registrar.applicants.reset-password');
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
        $this->requirePermission('registrar.applicants.status-update');
        $this->assertSameSchool($applicant);
        $request->validate([
            'status' => 'required|in:pending,reviewed,admitted,rejected',
            // Optional placement override on admit — same shape as
            // ApplicationController::updateStatus. When the registrar
            // flips status to 'admitted' AND supplies a new
            // department/programme/school, we honour the override
            // before reserving the matric so the eventual Student row
            // (created lazily by ApplicantPaymentService::
            // migrateApplicantToStudent) lands in the correct place.
            'department_id' => 'nullable|exists:departments,id',
            'programme_id' => 'nullable|exists:programmes,id',
            'school_id' => 'nullable|exists:schools,id',
        ]);

        $payload = ['status' => $request->status];

        if ($request->status === 'admitted') {
            if ($request->filled('department_id')) {
                $payload['department_id'] = $request->department_id;
            }
            if ($request->filled('programme_id')) {
                $payload['programme_id'] = $request->programme_id;
            }
            if ($request->filled('school_id')) {
                $payload['school_id'] = $request->school_id;
            }
        }

        $applicant->update($payload);

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
                    // ENUM-safe value — see ApplicantPaymentService::feeTypeFor().
                    // payments.fee_type on production is an ENUM
                    // ('application','acceptance','school_fees','hostel',
                    // 'library','other'); 'application_fee' is not a valid
                    // value and would raise 'Data truncated for column fee_type'.
                    'fee_type' => 'application',
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
                    // ENUM-safe value — see ApplicantPaymentService::feeTypeFor().
                    'fee_type' => 'application',
                ]);
            }
        }
    }

    public function upload(Request $request)
    {
        $this->requirePermission('registrar.admissions.bulk-upload');

        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls',
        ]);

        // Excel upload would be implemented here
        return back()->with('info', 'Bulk upload feature - implementation pending');
    }

    public function settings()
    {
        $this->requirePermission('registrar.settings.view');

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
        $this->requirePermission('registrar.settings.edit');

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
        $this->requirePermission('registrar.admissions.view');

        $admitted = Applicant::where('status', 'admitted')->with('user', 'department')->get();
        return view('registrar.admission.print', compact('admitted'));
    }

    public function track(Request $request)
    {
        $this->requirePermission('registrar.admissions.track');

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
        $this->requirePermission('registrar.applicants.status-update');

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
        $this->requirePermission('registrar.settings.view');

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
        $this->requirePermission('registrar.settings.edit');

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
        $this->requirePermission('registrar.admissions.generate-letter');

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
        $this->requirePermission('registrar.admissions.generate-letter');
        $this->assertSameSchool($applicant);
        if ($applicant->status !== 'admitted') {
            return back()->with('error', 'Applicant is not admitted.');
        }

        $student = Student::where('matric_number', $applicant->matric_number)->first();

        // Resolve the registrar signature via the shared concern so the
        // applicant-side and student-side print endpoints agree on the
        // on-disk location — see ResolvesRegistrarSignature for the
        // resolution order (new public/uploads/ first, legacy storage/
        // fallback, fixed-file fallback).
        return view('registrar.admission.letter', [
            'applicant'    => $applicant,
            'student'      => $student,
            'signatureUrl' => $this->resolveRegistrarSignatureUrl(),
        ]);
    }

    /**
     * Upload admission list by department (bulk)
     */
    public function uploadAdmissionList(Request $request)
    {
        $this->requirePermission('registrar.admissions.bulk-upload');

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
        $this->requirePermission('registrar.admissions.view');

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
        $this->requirePermission('registrar.admissions.bulk-upload');

        $departments = \App\Models\Department::with('school')->get();
        return view('registrar.admission.upload-list', compact('departments'));
    }

    /**
     * Show the editable letter settings (body, fees, signature)
     */
    public function showLetterSettings()
    {
        $this->requirePermission('registrar.settings.view');
        return view('registrar.admission.letters');
    }

    /**
     * Save admission letter settings (body, fees, institution details, registrar name + signature)
     */
    public function saveLetterSettings(Request $request)
    {
        $this->requirePermission('registrar.settings.edit');

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

            // Handle signature upload.
            //
            // Writes directly into public/uploads/signatures/ (matching the
            // public/uploads/passports/ pattern used by the student/applicant
            // profile uploads) so the file is served from the public web
            // root without going through the storage symlink. Three reasons
            // we DON'T route through Storage::disk('public') anymore:
            //   1. The user has a fixed asset path
            //      (public/uploads/signatures/registrar_signature.{ext})
            //      they want to drop a replacement file into — direct disk
            //      access mirrors that workflow.
            //   2. Avoids the Storage::fake() indirection in feature
            //      tests — direct $file->move() makes the on-disk location
            //      unambiguous and matches the convention used by the
            //      passport upload controller (Student\ProfileController).
            //   3. The views already read from both locations (this is the
            //      primary, the storage/ path is the legacy fallback for
            //      rows uploaded before this commit).
            //
            // The move is wrapped in its own try/catch so the OTHER settings
            // (body, fees, letterhead, registrar name) still persist even
            // when the filesystem refuses the write — most commonly because
            // the PHP-FPM user can't write into public/uploads on production.
            // On error we surface a clear flash the registrar can act on.
            if ($request->hasFile('registrar_signature')) {
                try {
                    $file = $request->file('registrar_signature');
                    $ext = $file->getClientOriginalExtension() ?: 'png';
                    $filename = 'registrar_signature.' . $ext;

                    // Ensure the destination directory exists with the same
                    // permissions passport uploads rely on. Idempotent —
                    // mkdir -p semantics.
                    $destinationDir = public_path('uploads/signatures');
                    if (! is_dir($destinationDir)) {
                        @mkdir($destinationDir, 0775, true);
                    }

                    // Delete the previous file (any extension) so a
                    // .png -> .jpg swap doesn't leave the old .png
                    // on disk serving stale content. Check both the new
                    // public/uploads location AND the legacy storage
                    // location so old signatures get cleaned up too.
                    $existing = SystemSetting::get('registrar_signature_path');
                    foreach ($this->signatureCandidatePaths($existing) as $candidate) {
                        if (is_file($candidate)) {
                            @unlink($candidate);
                        }
                    }

                    $file->move($destinationDir, $filename);

                    // Store the relative URL — views render via asset().
                    // Keep this as a path RELATIVE to public/ so the
                    // asset() helper builds the right URL regardless of
                    // whether the app is served from the root or a
                    // subdirectory.
                    SystemSetting::set('registrar_signature_path', 'uploads/signatures/' . $filename);
                } catch (\Throwable $fileError) {
                    // Don't roll back the rest of the settings — body, fees,
                    // letterhead and registrar name are already persisted.
                    // Surface the failure so the registrar knows the
                    // signature didn't land and can fix filesystem perms.
                    \Log::error('signature upload failed: ' . $fileError->getMessage());
                    return back()->with(
                        'error',
                        'Letter settings saved, but the signature file could not be uploaded: '
                        . $fileError->getMessage()
                    );
                }
            }

            return back()->with('success', 'Letter settings saved successfully.');
        } catch (\Throwable $e) {
            \Log::error('saveLetterSettings failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to save letter settings: ' . $e->getMessage());
        }
    }

    /**
     * Build the list of absolute filesystem paths where the registrar
     * signature might live, given the path stored in
     * system_settings.registrar_signature_path.
     *
     * After the upload target move the canonical value is
     * 'uploads/signatures/registrar_signature.{ext}' (a public-relative
     * path). Older rows may carry the legacy 'signatures/registrar_…'
     * value that used to live under Storage::disk('public') →
     * storage/app/public/. Both resolve to a real on-disk file so the
     * upload controller can clean up whichever it finds.
     *
     * @return array<int,string>
     */
    private function signatureCandidatePaths(?string $storedPath): array
    {
        if (! $storedPath) {
            return [];
        }

        $candidates = [];

        // If the stored path is a public-relative URL ('uploads/...' or
        // 'storage/...'), public_path() resolves it directly.
        $publicHit = public_path($storedPath);
        if (is_file($publicHit)) {
            $candidates[] = $publicHit;
        }

        // Legacy storage location — Storage::disk('public') write into
        // storage/app/public/<file>. The public/storage symlink also
        // surfaces them at public_path('storage/<file>').
        $storageHit = public_path('storage/' . ltrim($storedPath, '/'));
        if (is_file($storageHit)) {
            $candidates[] = $storageHit;
        }

        // Also try the raw storage/app/public path for completeness when
        // the symlink is missing or broken.
        $appPublicHit = storage_path('app/public/' . ltrim($storedPath, '/'));
        if (is_file($appPublicHit)) {
            $candidates[] = $appPublicHit;
        }

        return $candidates;
    }

    /**
     * Partial auto-save for one field, hit via fetch() on blur.
     *
     * The full Save-Letter-Settings form still works for body and
     * letterhead; this endpoint exists so the registrar_name input and
     * each acceptance-fee row can save as soon as the registrar leaves
     * the field — no scroll back to the master Save button.
     *
     * Mirrors the trim/clean logic from saveLetterSettings so the master
     * POST and the AJAX PATCH produce identical system_settings rows.
     *
     * Allowed fields: 'registrar_name' (string), 'fees' (array of
     * {name:string, amount:numeric}). Unknown field → 422 validation.
     */
    public function saveLetterField(Request $request)
    {
        $this->requirePermission('registrar.settings.edit');

        $payload = $request->validate([
            'field' => 'required|in:registrar_name,fees',
            'value' => 'present',
        ]);

        try {
            if ($payload['field'] === 'registrar_name') {
                $name = trim((string) $request->input('value', ''));
                SystemSetting::set('registrar_name', $name);

                return response()->json([
                    'ok' => true,
                    'field' => 'registrar_name',
                    'saved_at' => now()->toIso8601String(),
                ]);
            }

            if ($payload['field'] === 'fees') {
                $raw = $request->input('value', []);
                if (! is_array($raw)) {
                    return response()->json([
                        'ok' => false,
                        'field' => 'fees',
                        'error' => 'fees must be an array',
                    ], 422);
                }

                $clean = [];
                foreach ($raw as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $name = trim((string) ($row['name'] ?? ''));
                    $amt  = isset($row['amount']) ? (float) $row['amount'] : 0;
                    if ($name !== '' && $amt > 0) {
                        $clean[] = ['name' => $name, 'amount' => $amt];
                    }
                }
                SystemSetting::set('admission_letter_fees', json_encode($clean));

                return response()->json([
                    'ok' => true,
                    'field' => 'fees',
                    'saved_at' => now()->toIso8601String(),
                    'count' => count($clean),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('saveLetterField failed: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'field' => $payload['field'],
                'error' => $e->getMessage(),
            ], 500);
        }

        // Defensive — validate() should have caught this already.
        return response()->json([
            'ok' => false,
            'field' => $payload['field'],
            'error' => 'Unhandled field',
        ], 422);
    }

    /**
     * Delete the registrar signature.
     *
     * The signature may live in either the new public/uploads/signatures/
     * directory or the legacy storage/app/public/signatures/ directory
     * depending on when it was uploaded. Try both locations so a delete
     * always lands even on rows from before the upload-target move.
     */
    public function deleteSignature()
    {
        $this->requirePermission('registrar.settings.edit');

        try {
            $existing = SystemSetting::get('registrar_signature_path');
            foreach ($this->signatureCandidatePaths($existing) as $candidate) {
                if (is_file($candidate)) {
                    @unlink($candidate);
                }
            }
            SystemSetting::set('registrar_signature_path', '');
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