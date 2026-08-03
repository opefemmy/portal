<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        // Only show pending and processing applicants, not admitted ones
        $query = Applicant::with('user', 'department', 'programme', 'school', 'session');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('application_number', 'like', "%{$request->search}%")
                  ->orWhere('surname', 'like', "%{$request->search}%")
                  ->orWhere('first_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->school_id) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        $applications = $query->whereIn('status', ['pending', 'screening', 'approved'])
            ->latest()
            ->paginate(20);
        $schools = \App\Models\School::all();
        $departments = \App\Models\Department::all();

        // Reuse the existing applications.index view (statistics, filters, table already built).
        return view('registrar.applications.index', compact('applications', 'schools', 'departments'));
    }

    public function show(Applicant $applicant)
    {
        $this->assertSameSchool($applicant);
        $applicant->load('user', 'department', 'programme', 'school', 'session', 'state', 'lga');
        // Reuse the existing admission.show view (same Applicant model).
        return view('registrar.admission.show', compact('applicant'));
    }

    public function admit(Applicant $applicant, Request $request)
    {
        $this->assertSameSchool($applicant);
        DB::beginTransaction();

        try {
            $matricNumber = \App\Services\MatricNumberService::generate($applicant);

            // Reserve the matric number on the applicant row but DO NOT
            // create the Student row yet, and DO NOT promote the user to
            // the student role. Both happen when the applicant pays the
            // compulsory fee (see ApplicantPaymentService::migrateApplicantToStudent).
            $applicant->update([
                'status' => 'admitted',
                'matric_number' => $matricNumber,
                'admission_date' => now(),
                'remarks' => $request->remarks,
            ]);

            DB::commit();

            return back()->with('success', 'Applicant admitted. Matric number reserved: ' . $matricNumber . '. Student record will be created when the compulsory fee is paid.');
        } catch (\Exception $e) {
            DB::rollback();

            return back()->with('error', 'Failed to admit applicant: ' . $e->getMessage());
        }
    }

    public function reject(Applicant $applicant, Request $request)
    {
        $this->assertSameSchool($applicant);
        $applicant->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Applicant rejected');
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
