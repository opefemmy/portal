<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Models\Role;
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

        // If admitted, create student record
        if ($request->status === 'admitted' && !$applicant->student) {
            $this->createStudentFromApplicant($applicant);
        }

        return back()->with('success', 'Admission status updated');
    }

    protected function createStudentFromApplicant(Applicant $applicant)
    {
        DB::transaction(function () use ($applicant) {
            $user = User::create([
                'name' => $applicant->full_name,
                'email' => $applicant->email,
                'password' => Hash::make('student123'),
                'role_id' => 9, // Student role
                'is_active' => true,
            ]);

            Student::create([
                'user_id' => $user->id,
                'matric_number' => $this->generateMatricNumber($applicant),
                'school_id' => $applicant->school_id,
                'department_id' => $applicant->department_id,
                'programme_id' => $applicant->programme_id,
                'session_id' => Setting::get('session_id'),
                'level' => 1,
                'status' => 'active',
            ]);

            $applicant->update(['student_created' => true]);
        });
    }

    protected function generateMatricNumber(Applicant $applicant)
    {
        $year = date('Y');
        $count = Applicant::whereYear('created_at', $year)->count() + 1;
        return $year . str_pad($count, 4, '0', STR_PAD_LEFT);
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
        $data = [
            'admission_letter_template' => Setting::get('admission_letter_template', ''),
            'admission_number_prefix' => Setting::get('admission_number_prefix', 'ADM'),
            'auto_create_student' => Setting::get('auto_create_student', true),
        ];
        return view('registrar.admission.settings', $data);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'admission_letter_template' => 'nullable|string',
            'admission_number_prefix' => 'nullable|string|max:10',
            'auto_create_student' => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
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
}