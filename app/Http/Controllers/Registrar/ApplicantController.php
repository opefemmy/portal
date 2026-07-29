<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Student;
use App\Models\User;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ApplicantController extends Controller
{
    public function index()
    {
        // Only show pending and processing applicants, not admitted ones
        $applicants = Applicant::with('user', 'department', 'programme', 'school')
            ->whereIn('status', ['pending', 'screening', 'approved'])
            ->latest()
            ->get();
        return view('registrar.applicants.index', compact('applicants'));
    }

    public function show(Applicant $applicant)
    {
        $applicant->load('user', 'department', 'programme', 'school', 'session', 'state', 'lga');
        return view('registrar.applicants.show', compact('applicant'));
    }

    public function admit(Applicant $applicant, Request $request)
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Generate matric number
            $matricNumber = $this->generateMatricNumber($applicant);

            // Update applicant status
            $applicant->update([
                'status' => 'admitted',
                'matric_number' => $matricNumber,
                'admission_date' => now(),
                'remarks' => $request->remarks,
            ]);

            // Check if user exists and update role to student
            if ($applicant->user) {
                // Find student role
                $studentRole = \App\Models\Role::where('slug', 'student')->first();

                if ($studentRole) {
                    $applicant->user->update([
                        'role_id' => $studentRole->id,
                        'matric_number' => $matricNumber,
                    ]);
                }
            }

            // Create student record
            $currentSession = Session::where('is_current', true)->first();

            Student::create([
                'user_id' => $applicant->user_id,
                'matric_number' => $matricNumber,
                'school_id' => $applicant->school_id,
                'department_id' => $applicant->department_id,
                'programme_id' => $applicant->programme_id,
                'session_id' => $currentSession?->id,
                'level' => 1,
                'status' => 'active',
            ]);

            DB::commit();

            return back()->with('success', 'Applicant admitted successfully! Matric Number: ' . $matricNumber);
        } catch (\Exception $e) {
            DB::rollback();

            return back()->with('error', 'Failed to admit applicant: ' . $e->getMessage());
        }
    }

    public function reject(Applicant $applicant, Request $request)
    {
        $applicant->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Applicant rejected');
    }

    /**
     * Generate a unique matric number
     */
    private function generateMatricNumber(Applicant $applicant): string
    {
        $year = date('Y');
        $departmentCode = strtoupper(substr($applicant->department?->name ?? 'XX', 0, 2));

        // Check for existing matric numbers with similar prefix
        $existingCount = Student::where('matric_number', 'like', $departmentCode . $year . '%')
            ->count();

        $sequence = str_pad($existingCount + 1, 4, '0', STR_PAD_LEFT);

        return $departmentCode . $year . $sequence;
    }
}
