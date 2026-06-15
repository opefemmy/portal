<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Applicant;
use App\Models\Payment;
use App\Models\Session;
use App\Models\School;
use App\Models\Department;
use App\Models\Course;
use App\Models\StudentCourse;
use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $currentSession = Session::getCurrentSession();

        // Get current session ID
        $sessionId = $currentSession?->id;

        // Calculate total expected fees
        $totalExpectedFees = Fee::where('session_id', $sessionId)->sum('amount');
        $totalPayments = Payment::where('status', 'completed')->sum('amount');

        $stats = [
            'total_students' => Student::count(),
            'total_applicants' => Applicant::count(),
            'total_courses' => Course::count(),
            'total_schools' => School::count(),
            'total_departments' => Department::count(),
            'total_users' => User::count(),
            'total_staff' => User::whereHas('role', function($q) {
                $q->whereIn('slug', ['lecturer', 'hod', 'dean', 'registrar', 'bursar', 'admin', 'staff']);
            })->count(),
            'pending_applications' => Applicant::where('status', 'pending')->count(),
            'admitted_students' => Applicant::where('status', 'admitted')->count(),
            'registered_courses' => StudentCourse::where('status', 'registered')->count(),
            'total_expected_fees' => $totalExpectedFees,
            'total_payments' => $totalPayments,
            'outstanding_fees' => $totalExpectedFees - $totalPayments,
        ];

        // Get student count by level
        $studentsByLevel = Student::select('level', DB::raw('count(*) as count'))
            ->groupBy('level')
            ->get();

        // Get payments by status
        $paymentsByStatus = Payment::select('status', DB::raw('count(*) as count, sum(amount) as total'))
            ->groupBy('status')
            ->get();

        $recentApplicants = Applicant::with(['user', 'department', 'programme'])
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = Payment::with(['student.user', 'fee'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'currentSession', 'recentApplicants', 'recentPayments', 'studentsByLevel', 'paymentsByStatus'));
    }
}