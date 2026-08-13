<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Applicant;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('registrar.applicants.view');

        $query = Applicant::with(['school', 'department', 'programme', 'session']);

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

        $applications = $query->latest()->paginate(20);
        $schools = School::all();
        $departments = Department::all();

        return view('registrar.applications.index', compact('applications', 'schools', 'departments'));
    }

    public function show(Applicant $applicant)
    {
        $this->requirePermission('registrar.applicants.view');
        $this->assertSameSchool($applicant);
        $applicant->load(['school', 'department', 'programme', 'session', 'user']);
        return view('registrar.applications.show', compact('applicant'));
    }

    public function updateStatus(Request $request, Applicant $applicant)
    {
        $this->requirePermission('registrar.applicants.status-update');
        $this->assertSameSchool($applicant);
        $request->validate([
            'status' => 'required|in:pending,screening,approved,rejected,admitted',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $applicant->update([
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Application status updated successfully!');
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
            abort(403, 'You are not allowed to access this application.');
        }
    }

    public function bulkAction(Request $request)
    {
        $this->requirePermission('registrar.applicants.status-update');

        $request->validate([
            'applications' => 'required|array',
            'action' => 'required|in:screening,approved,rejected,admitted',
        ]);

        // School isolation: every other registrar route goes through
        // assertSameSchool(), but the bulk endpoint was the lone
        // exception — a registrar could silently update applicants
        // from another school. Pre-filter the IDs so cross-school
        // rows are dropped before the write. Admin / super_admin
        // users with no school_id keep the legacy "all schools"
        // behaviour.
        $authUser = auth()->user();
        $authSchoolId = $authUser?->school_id;

        $query = Applicant::query()->whereIn('id', $request->applications);
        if ($authSchoolId) {
            $query->where('school_id', $authSchoolId);
        }

        $count = DB::transaction(function () use ($query, $request, $authUser) {
            return $query->update([
                'status' => $request->action,
                'reviewed_by' => $authUser?->id,
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', $count . ' applications updated successfully!');
    }

    public function export(Request $request)
    {
        $this->requirePermission('registrar.reports.export');

        $query = Applicant::with(['school', 'department', 'programme']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $applications = $query->get();

        // Simple CSV export
        $csv = "Application Number,Name,Email,Phone,Gender,School,Department,Programme,Status\n";
        foreach ($applications as $app) {
            $csv .= "{$app->application_number},{$app->first_name} {$app->surname},{$app->email},{$app->phone},{$app->gender},{$app->school->name},{$app->department->name},{$app->programme->name},{$app->status}\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'applications.csv', ['Content-Type' => 'text/csv']);
    }

    public function admittedStudents(Request $request)
    {
        $this->requirePermission('registrar.applicants.view');

        $query = Applicant::with(['school', 'department', 'programme', 'session'])
            ->where('status', 'admitted');

        if ($request->school_id) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->session_id) {
            $query->where('session_id', $request->session_id);
        }

        $students = $query->latest()->paginate(20);
        $schools = School::all();
        $departments = Department::all();
        $sessions = Session::all();

        return view('registrar.applications.admitted', compact('students', 'schools', 'departments', 'sessions'));
    }

    public function statistics()
    {
        $this->requirePermission('registrar.reports.view');

        $stats = [
            'total' => Applicant::count(),
            'pending' => Applicant::where('status', 'pending')->count(),
            'screening' => Applicant::where('status', 'screening')->count(),
            'approved' => Applicant::where('status', 'approved')->count(),
            'admitted' => Applicant::where('status', 'admitted')->count(),
            'rejected' => Applicant::where('status', 'rejected')->count(),
        ];

        $bySchool = Applicant::selectRaw('school_id, COUNT(*) as count')
            ->groupBy('school_id')
            ->with('school')
            ->get();

        $byDepartment = Applicant::selectRaw('department_id, COUNT(*) as count')
            ->groupBy('department_id')
            ->with('department')
            ->get();

        return view('registrar.applications.statistics', compact('stats', 'bySchool', 'byDepartment'));
    }
}