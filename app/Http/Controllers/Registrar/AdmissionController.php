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
        $query = Applicant::with(['user', 'department', 'school']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $applicants = $query->latest()->get();
        return view('registrar.admission.index', compact('applicants'));
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

            Student::create([
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
            ]);

            $applicant->update([
                'student_created' => true,
                'matric_number' => $matricNumber,
            ]);
        });
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
        // Get system settings for admission
        $settings = [
            'admission_form_open' => SystemSetting::get('admission_form_open', 'false'),
            'admission_form_penalty' => SystemSetting::get('admission_form_penalty', 0),
            'admission_form_penalty_amount' => SystemSetting::get('admission_form_penalty_amount', 0),
        ];

        return view('registrar.admission.settings', $settings);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'admission_form_open' => 'boolean',
            'admission_form_penalty' => 'boolean',
            'admission_form_penalty_amount' => 'nullable|numeric|min:0',
        ]);

        // Update settings
        SystemSetting::set('admission_form_open', $request->boolean('admission_form_open') ? 'true' : 'false');
        SystemSetting::set('admission_form_penalty', $request->boolean('admission_form_penalty') ? 'true' : 'false');
        if ($request->has('admission_form_penalty_amount')) {
            SystemSetting::set('admission_form_penalty_amount', $request->admission_form_penalty_amount);
        }

        // Also save to regular settings
        foreach ($validated as $key => $value) {
            if (!in_array($key, ['admission_form_open', 'admission_form_penalty', 'admission_form_penalty_amount'])) {
                Setting::set($key, $value);
            }
        }

        return redirect()->route('registrar.admission.settings')->with('success', 'Admission settings updated');
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
}