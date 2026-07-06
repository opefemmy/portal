<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Session;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentSession = Session::getCurrentSession();
        $schools = School::all();

        // Get payment statistics
        $totalExpected = Fee::where('session_id', $currentSession->id ?? 0)
            ->sum('amount');

        $totalPaid = Payment::whereHas('student', function($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id ?? 0);
            })
            ->where('status', 'verified')
            ->sum('amount');

        $totalPending = $totalExpected - $totalPaid;

        // Get debtors (students who haven't paid)
        $debtors = Student::where('session_id', $currentSession->id ?? 0)
            ->whereDoesntHave('payments', function($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id ?? 0)
                  ->where('status', 'verified');
            })
            ->with(['department', 'programme', 'user'])
            ->orderBy('matric_number')
            ->paginate(20);

        // Get paid students with details
        $paidStudents = Payment::where('status', 'verified')
            ->whereHas('student', function($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id ?? 0);
            })
            ->with(['student.user', 'student.department', 'student.programme'])
            ->orderByDesc('created_at')
            ->paginate(20);

        // Filter by school if provided
        if ($request->has('school_id') && $request->school_id) {
            $debtors = $debtors->filter(function($student) use ($request) {
                return $student->school_id == $request->school_id;
            });
            $paidStudents = Payment::where('status', 'verified')
                ->whereHas('student', function($q) use ($currentSession, $request) {
                    $q->where('session_id', $currentSession->id ?? 0)
                      ->where('school_id', $request->school_id);
                })
                ->with(['student.user', 'student.department', 'student.programme'])
                ->orderByDesc('created_at')
                ->paginate(20);
        }

        // Payment status summary
        $paymentStats = [
            'total_expected' => $totalExpected,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'debtors_count' => Student::where('session_id', $currentSession->id ?? 0)
                ->whereDoesntHave('payments', function($q) use ($currentSession) {
                    $q->where('session_id', $currentSession->id ?? 0)
                      ->where('status', 'verified');
                })->count(),
            'paid_count' => Payment::where('status', 'verified')
                ->whereHas('student', function($q) use ($currentSession) {
                    $q->where('session_id', $currentSession->id ?? 0);
                })->count(),
        ];

        return view('bursar.dashboard', compact(
            'debtors', 'paidStudents', 'paymentStats', 'schools', 'currentSession'
        ));
    }

    public function debtors(Request $request)
    {
        $currentSession = Session::getCurrentSession();

        $query = Student::where('session_id', $currentSession->id ?? 0)
            ->whereDoesntHave('payments', function($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id ?? 0)
                  ->where('status', 'verified');
            })
            ->with(['department', 'programme', 'user']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('matric_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $debtors = $query->orderBy('matric_number')->paginate(50);

        return view('bursar.debtors', compact('debtors'));
    }

    public function paidStudents(Request $request)
    {
        $currentSession = Session::getCurrentSession();

        $query = Payment::where('status', 'verified')
            ->whereHas('student', function($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id ?? 0);
            })
            ->with(['student.user', 'student.department', 'student.programme']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('matric_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $paidStudents = $query->orderByDesc('created_at')->paginate(50);

        return view('bursar.paid-students', compact('paidStudents'));
    }
}