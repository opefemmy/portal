<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
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
        $applicant->load(['school', 'department', 'programme', 'session', 'user']);
        return view('registrar.applications.show', compact('applicant'));
    }

    public function updateStatus(Request $request, Applicant $applicant)
    {
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

    public function bulkAction(Request $request)
    {
        $request->validate([
            'applications' => 'required|array',
            'action' => 'required|in:screening,approved,rejected,admitted',
        ]);

        $count = 0;
        foreach ($request->applications as $applicationId) {
            $applicant = Applicant::find($applicationId);
            if ($applicant) {
                $applicant->update([
                    'status' => $request->action,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
                $count++;
            }
        }

        return back()->with('success', $count . ' applications updated successfully!');
    }

    public function export(Request $request)
    {
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
        $stats = [
            'total' => Applicant::count(),
            'pending' => Applicant::where('status', 'pending')->count(),
            'screening' => Applicant::byStatus('screening')->count(),
            'approved' => Applicant::byStatus('approved')->count(),
            'admitted' => Applicant::byStatus('admitted')->count(),
            'rejected' => Applicant::byStatus('rejected')->count(),
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