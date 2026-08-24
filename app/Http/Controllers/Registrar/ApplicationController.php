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

        // Cascade Departments to the selected School. Without this the
        // dropdown lists every department across every school, which makes
        // picking a department under a specific school ambiguous. When no
        // school is selected, show every department. ordered by name to
        // match the existing admitted.blade.php convention.
        $departmentsQuery = Department::query();
        if ($request->school_id) {
            $departmentsQuery->where('school_id', $request->school_id);
        }
        $departments = $departmentsQuery->orderBy('name')->get();

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
            // Optional admission re-assignment — when status === 'admitted'
            // and the registrar picks a different department/programme/school
            // from what the applicant originally registered for, we honour
            // that override before reserving the matric number. Without this,
            // there was no path to admit an applicant into a department
            // they didn't originally apply to.
            'department_id' => 'nullable|exists:departments,id',
            'programme_id' => 'nullable|exists:programmes,id',
            'school_id' => 'nullable|exists:schools,id',
        ]);

        $payload = [
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ];

        // Only override the placement fields when the registrar is
        // admitting AND supplied new values. For every other status
        // (pending/screening/approved/rejected) we leave the original
        // school/department/programme alone — those statuses never
        // reassign placement, and silently rewriting them would be
        // surprising. If only one of the three is sent, we still
        // accept it; downstream Student::create uses whichever is set
        // on the applicant row at migration time.
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

            // Reserve a matric number if one isn't already on the
            // applicant row. We don't want a fresh matric every time
            // someone re-saves the form, so this is gated on null.
            if (empty($applicant->matric_number)) {
                $matric = \App\Services\MatricNumberService::generate($applicant);
                if ($matric) {
                    $payload['matric_number'] = $matric;
                }
            }
        }

        $applicant->update($payload);

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

        // Apply every filter the index form exposes so the CSV matches
        // what the registrar sees on screen. Previously this method only
        // honoured `status` — clicking "Export CSV" from a filtered view
        // returned the entire table. The Export link on the index view
        // forwards these four keys as a querystring.
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

        $applications = $query->latest()->get();

        // Simple CSV export. school/department/programme are BelongsTo
        // and any of them can be null if the FK row was deleted (e.g.
        // a school that was retired while applications still referenced
        // it). Use null-safe access with an 'N/A' fallback so a single
        // orphaned applicant row doesn't 500 the whole export — the
        // registrar still gets every other row. PHP's string-interpolation
        // parser doesn't accept `?->` mid-interpolation, so resolve the
        // names to local vars first.
        $csv = "Application Number,Name,Email,Phone,Gender,School,Department,Programme,Status\n";
        foreach ($applications as $app) {
            $schoolName     = $app->school?->name ?? 'N/A';
            $departmentName = $app->department?->name ?? 'N/A';
            $programmeName  = $app->programme?->name ?? 'N/A';
            $csv .= "{$app->application_number},{$app->first_name} {$app->surname},{$app->email},{$app->phone},{$app->gender},{$schoolName},{$departmentName},{$programmeName},{$app->status}\n";
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